<?php

namespace Database\Seeders;

use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Seed a realistic-looking inbox.
     *
     * SYNTHETIC DATA ONLY — never seed real customer PII (org policy).
     */
    public function run(): void
    {
        $agent = User::whereHas('roles', fn ($q) => $q->where('name', 'Support Agent'))->first()
            ?? User::first();

        // A few hand-written threads so the demo reads naturally.
        $threads = [
            [
                'subject' => 'Refund not received yet',
                'status' => ConversationStatus::Open,
                'messages' => [
                    [MessageSenderType::Customer, 'Hi, I returned my order last week but still no refund.'],
                    [MessageSenderType::Agent, 'Thanks for reaching out — let me check the status for you.'],
                    [MessageSenderType::Customer, 'Appreciate it, the order number is #10482.'],
                ],
            ],
            [
                'subject' => 'How do I reset my password?',
                'status' => ConversationStatus::Waiting,
                'messages' => [
                    [MessageSenderType::Customer, 'I can\'t log in and the reset email never arrives.'],
                    [MessageSenderType::Agent, 'Could you confirm the email address on the account?'],
                ],
            ],
            [
                'subject' => 'Billing question about my plan',
                'status' => ConversationStatus::Assigned,
                'messages' => [
                    [MessageSenderType::System, 'Conversation assigned to an agent.'],
                    [MessageSenderType::Customer, 'Why was I charged twice this month?'],
                ],
            ],
            [
                'subject' => 'Thanks for the quick help!',
                'status' => ConversationStatus::Closed,
                'messages' => [
                    [MessageSenderType::Customer, 'The issue is resolved now, thank you.'],
                    [MessageSenderType::Agent, 'Glad to hear it! Reach out anytime.'],
                ],
            ],
        ];

        foreach ($threads as $thread) {
            $customer = Customer::factory()->create();

            $conversation = Conversation::factory()
                ->for($customer)
                ->status($thread['status'])
                ->create([
                    'subject' => $thread['subject'],
                    'assigned_to' => in_array($thread['status'], [ConversationStatus::Assigned, ConversationStatus::Closed], true)
                        ? $agent?->id
                        : null,
                ]);

            $last = null;
            foreach ($thread['messages'] as $i => [$senderType, $body]) {
                $last = $conversation->messages()->create([
                    'sender_type' => $senderType,
                    'sender_id' => $senderType === MessageSenderType::Agent ? $agent?->id : null,
                    'type' => MessageType::Text,
                    'body' => $body,
                    // Customer messages in open/waiting threads stay unread.
                    'read_at' => $senderType === MessageSenderType::Customer
                        && in_array($thread['status'], [ConversationStatus::Open, ConversationStatus::Waiting], true)
                            ? null
                            : now(),
                    'created_at' => now()->subMinutes((count($thread['messages']) - $i) * 5),
                ]);
            }

            $conversation->forceFill(['last_message_at' => $last?->created_at])->save();
        }

        // Plus some random filler so pagination and counts look alive.
        Conversation::factory()
            ->count(8)
            ->has(Message::factory()->count(3)->fromCustomer(), 'messages')
            ->create();
    }
}
