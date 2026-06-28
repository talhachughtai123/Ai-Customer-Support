<?php

namespace Tests\Feature;

use App\Enums\MessageSenderType;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\ParticipantTyping;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_widget_page_is_publicly_accessible(): void
    {
        $this->get(route('widget.show'))->assertOk();
    }

    public function test_a_guest_can_start_a_chat_and_receive_a_token(): void
    {
        $response = $this->postJson(route('widget.start'), [
            'name' => 'Synthetic Visitor',
            'email' => 'visitor@example.test',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'conversation' => ['id', 'status', 'messages']]);

        $this->assertDatabaseHas('customers', ['email' => 'visitor@example.test']);
        $this->assertDatabaseHas('conversations', [
            'token' => $response->json('token'),
            'channel' => 'web',
        ]);
    }

    public function test_starting_a_chat_requires_a_name(): void
    {
        $this->postJson(route('widget.start'), ['email' => 'visitor@example.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('name');
    }

    public function test_starting_a_chat_reuses_an_existing_customer_by_email(): void
    {
        $existing = Customer::factory()->create(['email' => 'repeat@example.test']);

        $this->postJson(route('widget.start'), [
            'name' => 'Repeat Visitor',
            'email' => 'repeat@example.test',
        ])->assertCreated();

        $this->assertSame(1, Customer::where('email', 'repeat@example.test')->count());
        $this->assertSame(1, $existing->conversations()->count());
    }

    public function test_a_guest_can_post_a_message_with_a_valid_token(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::factory()->create();

        $response = $this->postJson(
            route('widget.messages.store', $conversation->token),
            ['body' => 'I need help with my order'],
        );

        $response->assertCreated()
            ->assertJsonPath('message.sender_type', 'customer')
            ->assertJsonPath('message.body', 'I need help with my order');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Customer->value,
            'body' => 'I need help with my order',
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            $channels = $event->broadcastOn();

            return $channels[0] instanceof PrivateChannel
                && $channels[0]->name === 'private-conversation.'.$conversation->id
                && $channels[1] instanceof Channel
                && $channels[1]->name === 'widget.'.$conversation->token;
        });
    }

    public function test_posting_a_message_with_an_unknown_token_returns_404(): void
    {
        $this->postJson(
            route('widget.messages.store', 'not-a-real-token'),
            ['body' => 'Hello?'],
        )->assertNotFound();
    }

    public function test_a_guest_can_resume_a_thread_by_token(): void
    {
        $conversation = Conversation::factory()->create();
        Message::factory()->count(3)->fromCustomer()->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->getJson(route('widget.thread', $conversation->token))
            ->assertOk()
            ->assertJsonCount(3, 'conversation.messages');
    }

    public function test_marking_agent_messages_read_dispatches_messages_read_with_token(): void
    {
        Event::fake([MessagesRead::class]);

        $agent = User::factory()->create();
        $conversation = Conversation::factory()->create();
        Message::factory()->fromAgent($agent->id)->create([
            'conversation_id' => $conversation->id,
            'read_at' => null,
        ]);

        $this->postJson(route('widget.read', $conversation->token))->assertStatus(202);

        Event::assertDispatched(MessagesRead::class, function (MessagesRead $event) use ($conversation) {
            return $event->reader === 'customer'
                && $event->token === $conversation->token
                && $event->broadcastOn()[1]->name === 'widget.'.$conversation->token;
        });
    }

    public function test_customer_typing_dispatches_participant_typing(): void
    {
        Event::fake([ParticipantTyping::class]);

        $conversation = Conversation::factory()->create();

        $this->postJson(route('widget.typing', $conversation->token))->assertStatus(202);

        Event::assertDispatched(ParticipantTyping::class, function (ParticipantTyping $event) use ($conversation) {
            return $event->side === 'customer' && $event->token === $conversation->token;
        });
    }
}
