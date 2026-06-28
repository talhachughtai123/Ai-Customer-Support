<?php

namespace App\Ai;

/**
 * Immutable generation settings handed to a provider. Provider-agnostic, so the
 * same config drives Gemini today and Groq/OpenAI/Ollama later.
 */
class AiConfig
{
    public function __construct(
        public readonly string $systemPrompt,
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 1024,
        public readonly ?string $model = null,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            systemPrompt: (string) config('ai.system_prompt'),
            temperature: (float) config('ai.temperature'),
            maxTokens: (int) config('ai.max_tokens'),
        );
    }
}
