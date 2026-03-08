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
    /**
     * @param string $agentClass Fully-qualified class name of the agent to run
     * @param string $prompt The user prompt for the agent
     * @param array<string, mixed> $options Agent run options
     * @param string|null $callbackUrl Optional URL to POST results to when the job completes
     */
    public function __construct(
        public readonly string $agentClass,
        public readonly string $prompt,
        public readonly array $options = [],
        public readonly ?string $callbackUrl = null,
    ) {
    }

    /**
     * Serialize the job to an array for queue transport.
     *
     * @return array{agentClass: string, prompt: string, options: array, callbackUrl: string|null}
     */
    public function toArray(): array
    {
        return [
            'agentClass' => $this->agentClass,
            'prompt' => $this->prompt,
            'options' => $this->options,
            'callbackUrl' => $this->callbackUrl,
        ];
    }

    /**
     * Deserialize a job from a queue-transported array.
     *
     * @param array{agentClass: string, prompt: string, options?: array, callbackUrl?: string|null} $data Serialised job data
     *
     * @return self The restored job
     */
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
