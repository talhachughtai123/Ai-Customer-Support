<?php

namespace App\Ai\Providers;

use App\Ai\AiConfig;
use App\Contracts\Ai\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Gemini (generativelanguage API). Free tier — uses an API key passed
 * as a query parameter. Only this class knows Gemini's wire format; everything
 * upstream talks to AiProviderInterface.
 */
class GeminiProvider implements AiProviderInterface
{
    public function chat(array $messages, AiConfig $config): string
    {
        $apiKey = (string) config('ai.gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $model = $config->model ?? (string) config('ai.gemini.model');
        $baseUrl = rtrim((string) config('ai.gemini.base_url'), '/');

        $contents = array_map(fn (array $m) => [
            // Gemini uses "model" for the assistant role.
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $response = Http::asJson()
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                'systemInstruction' => [
                    'parts' => [['text' => $config->systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $config->temperature,
                    'maxOutputTokens' => $config->maxTokens,
                ],
            ])
            ->throw();

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }
}
