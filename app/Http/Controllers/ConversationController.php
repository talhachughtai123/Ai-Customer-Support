<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Enums\ConversationStatus;
use App\Events\ConversationUpdated;
use App\Events\MessagesRead;
use App\Events\ParticipantTyping;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
    ) {}

    /**
     * Agent inbox — the conversation list pane, with no conversation open.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Conversation::class);

        return Inertia::render('Conversations/Index', $this->inboxProps($request));
    }

    /**
     * The same inbox, with one conversation opened in the window pane.
     */
    public function show(Request $request, Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        // Opening a conversation clears its unread customer messages.
        if ($this->messages->markCustomerMessagesRead($conversation) > 0) {
            event(new MessagesRead($conversation->id, 'agent', $conversation->token));
        }

        $conversation = $this->conversations->findWithMessages($conversation->id);

        return Inertia::render('Conversations/Index', [
            ...$this->inboxProps($request),
            'activeConversation' => $this->presentConversation($conversation),
        ]);
    }

    /**
     * Update conversation status (and assignment).
     */
    public function update(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'string', 'in:'.implode(',', ConversationStatus::values())],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $conversation = $this->conversations->update($conversation, $validated);

        event(new ConversationUpdated($conversation->load('assignedAgent')));

        return back();
    }

    /**
     * Broadcast that this agent is typing, so the customer widget can show it.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('reply', $conversation);

        event(new ParticipantTyping(
            $conversation->id,
            $conversation->token,
            $request->user()->name,
            'agent',
        ));

        return response()->json(status: 202);
    }

    /**
     * Shared inbox props: filtered list, active filters, sidebar counts.
     *
     * @return array<string, mixed>
     */
    private function inboxProps(Request $request): array
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ];

        $conversations = $this->conversations
            ->paginate(array_filter($filters))
            ->through(fn (Conversation $conversation) => $this->presentListItem($conversation));

        return [
            'conversations' => $conversations,
            'filters' => $filters,
            'statusCounts' => $this->conversations->statusCounts(),
            'statuses' => array_map(
                fn (ConversationStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ConversationStatus::cases(),
            ),
            'activeConversation' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentListItem(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status->value,
            'channel' => $conversation->channel,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => (int) ($conversation->unread_count ?? 0),
            'customer' => [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'email' => $conversation->customer->email,
                'avatar' => $conversation->customer->avatar,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'status' => $conversation->status->value,
            'channel' => $conversation->channel,
            'token' => $conversation->token,
            'customer' => [
                'id' => $conversation->customer->id,
                'name' => $conversation->customer->name,
                'email' => $conversation->customer->email,
                'avatar' => $conversation->customer->avatar,
            ],
            'assigned_agent' => $conversation->assignedAgent
                ? ['id' => $conversation->assignedAgent->id, 'name' => $conversation->assignedAgent->name]
                : null,
            'messages' => $conversation->messages->map(fn ($message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type->value,
                'sender_name' => $message->sender?->name,
                'type' => $message->type->value,
                'body' => $message->body,
                'read_at' => $message->read_at?->toIso8601String(),
                'created_at' => $message->created_at->toIso8601String(),
            ])->all(),
        ];
    }
}
