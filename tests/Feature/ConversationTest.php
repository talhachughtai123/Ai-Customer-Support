<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Enums\Role;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(Role $role): User
    {
        return User::factory()->create()->assignRole($role->value);
    }

    public function test_inbox_lists_conversations(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        Conversation::factory()->count(3)->create();

        $this->actingAs($agent)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Conversations/Index')
                ->has('conversations.data', 3)
                ->has('statuses', count(ConversationStatus::cases())));
    }

    public function test_inbox_can_be_filtered_by_status(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        Conversation::factory()->status(ConversationStatus::Open)->create();
        Conversation::factory()->status(ConversationStatus::Closed)->create();

        $this->actingAs($agent)
            ->get(route('conversations.index', ['status' => ConversationStatus::Open->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('conversations.data', 1));
    }

    public function test_opening_a_conversation_loads_messages_and_marks_customer_messages_read(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        $conversation = Conversation::factory()->create();
        Message::factory()->count(2)->fromCustomer()->create([
            'conversation_id' => $conversation->id,
            'read_at' => null,
        ]);

        $this->actingAs($agent)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('activeConversation.messages', 2));

        $this->assertSame(0, $conversation->messages()
            ->where('sender_type', MessageSenderType::Customer->value)
            ->whereNull('read_at')
            ->count());
    }

    public function test_agent_can_post_a_reply_and_last_message_at_is_bumped(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        $conversation = Conversation::factory()->create(['last_message_at' => null]);

        $this->actingAs($agent)
            ->post(route('conversations.messages.store', $conversation), ['body' => 'On it!'])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Agent->value,
            'sender_id' => $agent->id,
            'body' => 'On it!',
        ]);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_reply_requires_a_body(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        $conversation = Conversation::factory()->create();

        $this->actingAs($agent)
            ->post(route('conversations.messages.store', $conversation), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_agent_can_change_conversation_status(): void
    {
        $agent = $this->userWithRole(Role::SupportAgent);
        $conversation = Conversation::factory()->status(ConversationStatus::Open)->create();

        $this->actingAs($agent)
            ->patch(route('conversations.update', $conversation), ['status' => ConversationStatus::Closed->value])
            ->assertRedirect();

        $this->assertSame(ConversationStatus::Closed, $conversation->fresh()->status);
    }

    public function test_viewer_cannot_reply(): void
    {
        $viewer = $this->userWithRole(Role::Viewer);
        $conversation = Conversation::factory()->create();

        $this->actingAs($viewer)
            ->post(route('conversations.messages.store', $conversation), ['body' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('messages', ['body' => 'Nope']);
    }

    public function test_viewer_cannot_change_status(): void
    {
        $viewer = $this->userWithRole(Role::Viewer);
        $conversation = Conversation::factory()->status(ConversationStatus::Open)->create();

        $this->actingAs($viewer)
            ->patch(route('conversations.update', $conversation), ['status' => ConversationStatus::Closed->value])
            ->assertForbidden();

        $this->assertSame(ConversationStatus::Open, $conversation->fresh()->status);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('conversations.index'))->assertRedirect(route('login'));
    }
}
