<?php

namespace App\Services;

use App\Services\Contracts\AIServiceInterface;
use InvalidArgumentException;

class AIServiceFactory
{
    /**
     * Create an AI service instance based on configuration
     */
    public static function create(?string $provider = null): AIServiceInterface
    {
        $provider = $provider ?? config('services.ai_provider', 'gemini');

        return match ($provider) {
            'openai' => new OpenAIService,
            'gemini' => new GeminiService,
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }
}
