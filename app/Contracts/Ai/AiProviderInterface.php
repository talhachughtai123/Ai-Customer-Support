<?php

namespace App\Contracts\Ai;

use App\Ai\AiConfig;

interface AiProviderInterface
{
    /**
     * Generate an assistant reply for a normalized chat transcript.
     *
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $messages
     *                                                                                  Oldest-first conversation turns.
     * @return string the assistant's reply text
     */
    public function chat(array $messages, AiConfig $config): string;
}
