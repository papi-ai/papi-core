<?php

/*
 * This file is part of PapiAI,
 * A simple but powerful PHP library for building AI agents.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PapiAI\Core\Contracts;

use PapiAI\Core\Message;
use PapiAI\Core\Response;
use PapiAI\Core\StreamChunk;

/**
 * Contract for LLM chat providers.
 *
 * Every AI provider (Anthropic, OpenAI, etc.) must implement this interface
 * to participate in the PapiAI ecosystem. Handles both synchronous and streaming chat.
 */
interface ProviderInterface
{
    /**
     * Send a chat completion request.
     *
     * @param array<Message> $messages The conversation messages
     * @param array{
     *     model?: string,
     *     tools?: array,
     *     maxTokens?: int,
     *     temperature?: float,
     *     stopSequences?: array<string>,
     *     outputSchema?: array,
     * } $options Request options
     *
     * @return Response The completed response with text, tool calls, and usage stats
     */
    public function chat(array $messages, array $options = []): Response;

    /**
     * Stream a chat completion request.
     *
     * @param array<Message> $messages The conversation messages
     * @param array $options Request options
     * @return iterable<StreamChunk>
     */
    public function stream(array $messages, array $options = []): iterable;

    /**
     * Check if the provider supports tool calling.
     *
     * @return bool True if the provider can handle tool definitions and return tool calls
     */
    public function supportsTool(): bool;

    /**
     * Check if the provider supports vision/image inputs.
     *
     * @return bool True if the provider can process image content in messages
     */
    public function supportsVision(): bool;

    /**
     * Check if the provider supports structured output with JSON schema.
     *
     * @return bool True if the provider can constrain output to a given schema
     */
    public function supportsStructuredOutput(): bool;

    /**
     * Get the provider name.
     *
     * @return string A unique identifier for this provider (e.g., 'anthropic', 'openai')
     */
    public function getName(): string;
}
