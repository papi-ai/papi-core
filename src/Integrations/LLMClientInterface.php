<?php

namespace Papi\Core\Integrations;

/**
 * LLM Client Interface
 *
 * Abstract interface for LLM providers (OpenAI, Anthropic, etc.)
 * Allows AIAgent to work with any LLM provider without tight coupling.
 */
interface LLMClientInterface
{
    /**
     * Generate a chat completion response
     *
     * @param array $requestData Request data including messages, model, etc.
     * @return array Response data from the LLM provider
     */
    public function chat(array $requestData): array;

    /**
     * Get supported models for this provider
     *
     * @return array List of supported model names
     */
    public function getSupportedModels(): array;

    /**
     * Get provider name
     *
     * @return string Provider name (e.g., 'openai', 'anthropic')
     */
    public function getProviderName(): string;

    /**
     * Check if the provider supports tool calling
     *
     * @return bool True if tool calling is supported
     */
    public function supportsToolCalling(): bool;
}
