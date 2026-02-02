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

use PapiAI\Core\Response;

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
     */
    public function supportsTool(): bool;

    /**
     * Check if the provider supports vision/image inputs.
     */
    public function supportsVision(): bool;

    /**
     * Check if the provider supports structured output with JSON schema.
     */
    public function supportsStructuredOutput(): bool;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
