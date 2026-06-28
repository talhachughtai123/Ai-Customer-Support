<?php

namespace App\Ai;

use App\Contracts\Ai\AiProviderInterface;
use App\Enums\MessageSenderType;
use App\Models\Conversation;

/**
 * Turns a conversation's recent history into a provider call. This is the seam
 * where RAG (MVP 5) and tool/function calling (MVP 6) will later augment the
 * prompt — the rest of the pipeline stays the same.
 */
class AiReplyService
{
    public function __construct(
        private readonly AiProviderInterface $provider,
    ) {}

    /**
     * Generate an assistant reply for the given conversation, or null if there
     * is nothing to answer.
     */
    public function generate(Conversation $conversation): ?string
    {
        $limit = (int) config('ai.history_limit');

        $history = $conversation->messages()
            ->whereIn('sender_type', [
                MessageSenderType::Customer->value,
                MessageSenderType::Agent->value,
                MessageSenderType::Ai->value,
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        if ($history->isEmpty()) {
            return null;
        }

        $messages = $history->map(fn ($m) => [
            // Customer = user; agent and AI both speak as the assistant side.
            'role' => $m->sender_type === MessageSenderType::Customer ? 'user' : 'assistant',
            'content' => (string) $m->body,
        ])->all();

        return $this->provider->chat($messages, AiConfig::fromConfig());
    }
}
