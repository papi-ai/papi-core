<?php

namespace PapiAi\Core;

use PapiAi\Core\Providers\LLMProvider;
use PapiAi\Core\Providers\ClaudeProvider;
use PapiAi\Core\Providers\OpenAIProvider;

class Papi
{
    private LLMProvider $provider;

    public function __construct(string $providerName = 'claude')
    {
        $this->using($providerName);
    }

    public function using(string $providerName): self
    {
        switch (strtolower($providerName)) {
            case 'openai':
                $this->provider = new OpenAIProvider();
                break;
            case 'claude':
            default:
                $this->provider = new ClaudeProvider();
                break;
        }
        return $this;
    }

    public function complete(string $prompt, array $config = []): string
    {
        return $this->provider->complete($prompt, $config);
    }
    
    public function version(): string
    {
        return '0.1.0';
    }
}
