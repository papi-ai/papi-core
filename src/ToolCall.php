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
 * Immutable value object representing a tool invocation requested by an LLM.
 *
 * Contains the unique call ID, the tool name, and the arguments the model
 * wants to pass. Used to match tool results back to specific requests.
 */
final class ToolCall
{
    /**
     * @param string $id Unique identifier for this tool call (used to match results)
     * @param string $name The tool name the model wants to invoke
     * @param array<string, mixed> $arguments The arguments provided by the model
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $arguments,
    ) {
    }

    /**
     * Create from an Anthropic API tool_use content block.
     *
     * @param array{id: string, name: string, input?: array} $data Anthropic tool_use block
     *
     * @return self Parsed tool call
     */
    public static function fromAnthropic(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            arguments: $data['input'] ?? [],
        );
    }

    /**
     * Create from an OpenAI API tool_calls entry.
     *
     * @param array{id: string, function: array{name: string, arguments: string}} $data OpenAI tool call
     *
     * @return self Parsed tool call
     */
    public static function fromOpenAI(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['function']['name'],
            arguments: json_decode($data['function']['arguments'], true) ?? [],
        );
    }

    /**
     * Convert to a serialisable array representation.
     *
     * @return array{id: string, name: string, arguments: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}
