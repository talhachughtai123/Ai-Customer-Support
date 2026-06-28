<?php

namespace Tests\Feature;

use App\Ai\AiConfig;
use App\Ai\AiReplyService;
use App\Contracts\Ai\AiProviderInterface;
use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Events\MessageSent;
use App\Events\ParticipantTyping;
use App\Jobs\GenerateAiReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'broadcasting.default' => 'null',
            'ai.provider' => 'gemini',
            'ai.gemini.api_key' => 'test-key',
        ]);
    }

    private function fakeGemini(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $text]]]],
                ],
            ]),
        ]);
    }

    public function test_the_provider_binding_resolves_to_gemini_and_parses_a_reply(): void
    {
        $this->fakeGemini('Hello from Gemini');

        $reply = app(AiProviderInterface::class)->chat(
            [['role' => 'user', 'content' => 'hi']],
            AiConfig::fromConfig(),
        );

        $this->assertSame('Hello from Gemini', $reply);
    }

    public function test_the_reply_service_sends_conversation_history_to_the_provider(): void
    {
        $this->fakeGemini('Yes, we ship worldwide.');

        $conversation = Conversation::factory()->create();
        Message::factory()->fromCustomer()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Do you ship internationally?',
        ]);

        $reply = app(AiReplyService::class)->generate($conversation);

        $this->assertSame('Yes, we ship worldwide.', $reply);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generateContent')
            && $request['contents'][0]['parts'][0]['text'] === 'Do you ship internationally?');
    }

    public function test_a_customer_message_queues_an_ai_reply_when_unassigned(): void
    {
        Bus::fake();
        config(['ai.auto_reply' => true]);

        $conversation = Conversation::factory()->create(['assigned_to' => null]);

        $this->postJson(route('widget.messages.store', $conversation->token), ['body' => 'Hi'])
            ->assertCreated();

        Bus::assertDispatched(
            GenerateAiReply::class,
            fn (GenerateAiReply $job) => $job->conversationId === $conversation->id,
        );
    }

    public function test_no_ai_reply_is_queued_when_a_human_agent_is_assigned(): void
    {
        Bus::fake();
        config(['ai.auto_reply' => true]);

        $agent = User::factory()->create();
        $conversation = Conversation::factory()->create(['assigned_to' => $agent->id]);

        $this->postJson(route('widget.messages.store', $conversation->token), ['body' => 'Hi'])
            ->assertCreated();

        Bus::assertNotDispatched(GenerateAiReply::class);
    }

    public function test_no_ai_reply_is_queued_when_auto_reply_is_disabled(): void
    {
        Bus::fake();
        config(['ai.auto_reply' => false]);

        $conversation = Conversation::factory()->create(['assigned_to' => null]);

        $this->postJson(route('widget.messages.store', $conversation->token), ['body' => 'Hi'])
            ->assertCreated();

        Bus::assertNotDispatched(GenerateAiReply::class);
    }

    public function test_the_job_posts_an_ai_message_and_broadcasts_it(): void
    {
        Event::fake([MessageSent::class, ParticipantTyping::class]);
        $this->fakeGemini('Happy to help!');

        $conversation = Conversation::factory()->create(['assigned_to' => null]);
        Message::factory()->fromCustomer()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Can you help?',
        ]);

        (new GenerateAiReply($conversation->id))->handle(
            app(AiReplyService::class),
            app(MessageRepositoryInterface::class),
        );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'body' => 'Happy to help!',
        ]);
        Event::assertDispatched(MessageSent::class);
    }

    public function test_the_job_bails_if_a_human_took_over_while_queued(): void
    {
        Http::fake();

        $agent = User::factory()->create();
        $conversation = Conversation::factory()->create(['assigned_to' => $agent->id]);
        Message::factory()->fromCustomer()->create(['conversation_id' => $conversation->id]);

        (new GenerateAiReply($conversation->id))->handle(
            app(AiReplyService::class),
            app(MessageRepositoryInterface::class),
        );

        $this->assertDatabaseMissing('messages', ['sender_type' => 'ai']);
        Http::assertNothingSent();
    }
}
