<?php

namespace PapiAi\Core\Providers;

interface LLMProvider
{
    /**
     * Complete a prompt using the LLM provider.
     *
     * @param string $prompt The prompt to send to the LLM.
     * @param array $config Configuration options (model, max_tokens, etc.).
     * @return string The generated text.
     */
    public function complete(string $prompt, array $config = []): string;
}
