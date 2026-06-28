<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\Role;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConversationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    private function agent(): User
    {
        return User::factory()->create()->assignRole(Role::SupportAgent->value);
    }

    public function test_posting_a_reply_broadcasts_message_sent_on_the_conversation_channel(): void
    {
        Event::fake([MessageSent::class]);

        $agent = $this->agent();
        $conversation = Conversation::factory()->create();

        $this->actingAs($agent)
            ->post(route('conversations.messages.store', $conversation), ['body' => 'Hello there']);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            $channels = $event->broadcastOn();

            return $event->message->body === 'Hello there'
                && $event->broadcastAs() === 'message.sent'
                && $channels[0] instanceof PrivateChannel
                && $channels[0]->name === 'private-conversation.'.$conversation->id;
        });
    }

    public function test_changing_status_broadcasts_conversation_updated(): void
    {
        Event::fake([ConversationUpdated::class]);

        $agent = $this->agent();
        $conversation = Conversation::factory()->status(ConversationStatus::Open)->create();

        $this->actingAs($agent)
            ->patch(route('conversations.update', $conversation), ['status' => ConversationStatus::Closed->value]);

        Event::assertDispatched(ConversationUpdated::class, function (ConversationUpdated $event) {
            return $event->conversation->status === ConversationStatus::Closed
                && $event->broadcastWith()['status'] === 'closed';
        });
    }

    public function test_opening_a_conversation_with_unread_messages_broadcasts_messages_read(): void
    {
        Event::fake([MessagesRead::class]);

        $agent = $this->agent();
        $conversation = Conversation::factory()->create();
        Message::factory()->count(2)->fromCustomer()->create([
            'conversation_id' => $conversation->id,
            'read_at' => null,
        ]);

        $this->actingAs($agent)->get(route('conversations.show', $conversation));

        Event::assertDispatched(MessagesRead::class, function (MessagesRead $event) use ($conversation) {
            return $event->conversationId === $conversation->id && $event->reader === 'agent';
        });
    }

    public function test_opening_a_conversation_without_unread_does_not_broadcast(): void
    {
        Event::fake([MessagesRead::class]);

        $agent = $this->agent();
        $conversation = Conversation::factory()->create();
        Message::factory()->count(2)->fromCustomer()->read()->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->actingAs($agent)->get(route('conversations.show', $conversation));

        Event::assertNotDispatched(MessagesRead::class);
    }
}
