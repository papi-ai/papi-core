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

namespace PapiAI\Core;

/**
 * Represents a request to an agent, passed through the middleware pipeline.
 */
final class AgentRequest
{
    /**
     * @param string $prompt The user prompt
     * @param array<string, mixed> $options Agent run options (e.g., outputSchema, maxTurns)
     * @param array<Message> $messages Pre-existing conversation messages
     * @param array<string, mixed> $metadata Arbitrary metadata for middleware inspection
     */
    public function __construct(
        public readonly string $prompt,
        public readonly array $options = [],
        public readonly array $messages = [],
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Return a new request with an additional option set.
     *
     * @param string $key Option key
     * @param mixed $value Option value
     *
     * @return self A new immutable request with the option merged
     */
    public function withOption(string $key, mixed $value): self
    {
        return new self(
            prompt: $this->prompt,
            options: array_merge($this->options, [$key => $value]),
            messages: $this->messages,
            metadata: $this->metadata,
        );
    }

    /**
     * Return a new request with the message history replaced.
     *
     * @param array<Message> $messages The new message list
     *
     * @return self A new immutable request with the updated messages
     */
    public function withMessages(array $messages): self
    {
        return new self(
            prompt: $this->prompt,
            options: $this->options,
            messages: $messages,
            metadata: $this->metadata,
        );
    }

    /**
     * Return a new request with an additional metadata entry.
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     *
     * @return self A new immutable request with the metadata merged
     */
    public function withMetadata(string $key, mixed $value): self
    {
        return new self(
            prompt: $this->prompt,
            options: $this->options,
            messages: $this->messages,
            metadata: array_merge($this->metadata, [$key => $value]),
        );
    }
}
