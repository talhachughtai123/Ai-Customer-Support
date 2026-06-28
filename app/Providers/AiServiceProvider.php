<?php

namespace App\Providers;

use App\Ai\Providers\GeminiProvider;
use App\Contracts\Ai\AiProviderInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Bind AiProviderInterface to the implementation named in config('ai.provider').
     * Adding a new provider = one class + one entry here; nothing else changes.
     *
     * @var array<string, class-string<AiProviderInterface>>
     */
    private array $providers = [
        'gemini' => GeminiProvider::class,
    ];

    public function register(): void
    {
        $this->app->bind(AiProviderInterface::class, function () {
            $name = (string) config('ai.provider');

            if (! isset($this->providers[$name])) {
                throw new InvalidArgumentException("Unsupported AI provider [{$name}].");
            }

            return $this->app->make($this->providers[$name]);
        });
    }
}
