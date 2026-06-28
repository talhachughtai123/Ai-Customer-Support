<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Enums\MessageType;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\ParticipantTyping;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, unauthenticated endpoints powering the website live-chat widget.
 *
 * A visitor has no account: the conversation's unguessable UUID `token` is the
 * only credential. It is minted on start, stored client-side, and required on
 * every subsequent call.
 */
class WidgetController extends Controller
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MessageRepositoryInterface $messages,
    ) {}

    /**
     * Render the standalone floating widget page (demo host).
     */
    public function show(): Response
    {
        return Inertia::render('Widget/Index');
    }

    /**
     * Start a new chat: create (or match) the customer and open a conversation.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $customer = $this->resolveCustomer($validated);

        $conversation = $this->conversations->create([
            'customer_id' => $customer->id,
            'status' => ConversationStatus::Open->value,
            'channel' => 'web',
        ]);

        return response()->json([
            'token' => $conversation->token,
            'conversation' => $this->presentConversation($conversation),
        ], 201);
    }

    /**
     * Resume an existing chat by token.
     */
    public function thread(string $token): JsonResponse
    {
        $conversation = $this->resolveByToken($token);

        return response()->json([
            'conversation' => $this->presentConversation(
                $this->conversations->findWithMessages($conversation->id),
            ),
        ]);
    }

    /**
     * The customer posts a message into the conversation.
     */
    public function storeMessage(Request $request, string $token): JsonResponse
    {
        $conversation = $this->resolveByToken($token);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->messages->create($conversation, [
            'sender_type' => MessageSenderType::Customer,
            'sender_id' => null,
            'type' => MessageType::Text,
            'body' => $validated['body'],
        ]);

        // Re-opening a closed thread the customer keeps writing in.
        if ($conversation->status === ConversationStatus::Closed) {
            $this->conversations->update($conversation, ['status' => ConversationStatus::Open->value]);
        }

        event(new MessageSent($message->setRelation('conversation', $conversation)));

        return response()->json(['message' => $this->presentMessage($message)], 201);
    }

    /**
     * The customer read the agent's messages — flip them to "read".
     */
    public function markRead(string $token): JsonResponse
    {
        $conversation = $this->resolveByToken($token);

        if ($this->messages->markAgentMessagesRead($conversation) > 0) {
            event(new MessagesRead($conversation->id, 'customer', $conversation->token));
        }

        return response()->json(status: 202);
    }

    /**
     * Broadcast that the customer is typing, so the agent inbox can show it.
     */
    public function typing(string $token): JsonResponse
    {
        $conversation = $this->resolveByToken($token);

        event(new ParticipantTyping(
            $conversation->id,
            $conversation->token,
            $conversation->customer->name,
            'customer',
        ));

        return response()->json(status: 202);
    }

    /**
     * Match an existing customer by email when given, otherwise create a fresh one.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['email'])) {
            return Customer::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name']],
            );
        }

        return Customer::create(['name' => $data['name']]);
    }

    private function resolveByToken(string $token): Conversation
    {
        return Conversation::where('token', $token)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status->value,
            'messages' => $conversation->relationLoaded('messages')
                ? $conversation->messages->map(fn (Message $m) => $this->presentMessage($m))->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type->value,
            'sender_name' => $message->sender?->name,
            'type' => $message->type->value,
            'body' => $message->body,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
