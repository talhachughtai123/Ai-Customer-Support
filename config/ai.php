<?php

return [
    // Which provider implementation to bind behind AiProviderInterface.
    'provider' => env('AI_PROVIDER', 'gemini'),

    // When true, a customer message with no human agent assigned gets an
    // automatic AI reply. When false, AI is suggest-only / off.
    'auto_reply' => (bool) env('AI_AUTO_REPLY', true),

    // How many recent messages to feed the model as session memory.
    'history_limit' => (int) env('AI_HISTORY_LIMIT', 1),

    'system_prompt' => env('AI_SYSTEM_PROMPT', <<<'PROMPT'
        You are a helpful customer-support assistant for an online business.
        Be concise, friendly and professional. Answer the customer's question
        directly. If you are unsure or the request needs a human (refunds,
        payments, account changes, complaints), say so and offer to connect
        them with a human agent. Never invent order numbers, prices or policies.
        PROMPT),

    'temperature' => (float) env('AI_TEMPERATURE', 0.7),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1024),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],
];
