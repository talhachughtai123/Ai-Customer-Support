<?php

namespace App\Jobs;

use App\Ai\AiReplyService;
use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Enums\MessageType;
use App\Events\MessageSent;
use App\Events\ParticipantTyping;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates and posts an AI assistant reply for a conversation. Queued because
 * the model call is slow; the resulting message reuses the normal MessageSent
 * broadcast, so it reaches the widget and the agent inbox like any other reply.
 */
class GenerateAiReply implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $conversationId) {}

    public function handle(AiReplyService $ai, MessageRepositoryInterface $messages): void
    {
        $conversation = Conversation::find($this->conversationId);
        // Bail if it vanished or a human took over while we were queued.
        if (! $conversation || $conversation->assigned_to !== null
            || $conversation->status === ConversationStatus::Closed) {
            return;
        }
        // Show "typing…" in the widget while the model thinks.
        event(new ParticipantTyping($conversation->id, $conversation->token, 'Assistant', 'ai'));

        try {
            $reply = $ai->generate($conversation);
        } catch (Throwable $e) {
            Log::warning('AI reply generation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($reply === null || trim($reply) === '') {
            return;
        }

        $message = $messages->create($conversation, [
            'sender_type' => MessageSenderType::Ai,
            'sender_id' => null,
            'type' => MessageType::Text,
            'body' => $reply,
        ]);

        event(new MessageSent($message->setRelation('conversation', $conversation)));
    }
}
