<?php

namespace Papi\Core\Integrations;

/**
 * OpenAIClient - Interface for OpenAI API integration
 *
 * Provides a contract for making calls to OpenAI's API,
 * allowing for both real and mock implementations.
 */
interface OpenAIClient
{
    /**
     * Make a chat completion request to OpenAI
     *
     * @param array $context The context including model, messages, tools, etc.
     * @return array The OpenAI API response
     */
    public function chat(array $context): array;
}
