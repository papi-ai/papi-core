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

final class ToolCall
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $arguments,
    ) {}

    /**
     * Create from Anthropic API format.
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
     * Create from OpenAI API format.
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
     * Convert to array.
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
