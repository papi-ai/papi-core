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
    public function __construct(
        public readonly string $prompt,
        public readonly array $options = [],
        public readonly array $messages = [],
        public readonly array $metadata = [],
    ) {
    }

    public function withOption(string $key, mixed $value): self
    {
        return new self(
            prompt: $this->prompt,
            options: array_merge($this->options, [$key => $value]),
            messages: $this->messages,
            metadata: $this->metadata,
        );
    }

    public function withMessages(array $messages): self
    {
        return new self(
            prompt: $this->prompt,
            options: $this->options,
            messages: $messages,
            metadata: $this->metadata,
        );
    }

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
