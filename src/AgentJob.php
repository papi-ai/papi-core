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
 * Represents an agent job that can be dispatched to a queue.
 */
final class AgentJob
{
    public function __construct(
        public readonly string $agentClass,
        public readonly string $prompt,
        public readonly array $options = [],
        public readonly ?string $callbackUrl = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'agentClass' => $this->agentClass,
            'prompt' => $this->prompt,
            'options' => $this->options,
            'callbackUrl' => $this->callbackUrl,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            agentClass: $data['agentClass'],
            prompt: $data['prompt'],
            options: $data['options'] ?? [],
            callbackUrl: $data['callbackUrl'] ?? null,
        );
    }
}
