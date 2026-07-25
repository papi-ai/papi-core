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

use ArrayAccess;
use LogicException;

/**
 * Immutable, provider-neutral token usage for a request.
 *
 * Normalises the different key names providers use (Anthropic `input_tokens`/`output_tokens`,
 * OpenAI `prompt_tokens`/`completion_tokens`) into `inputTokens`/`outputTokens`/`totalTokens`.
 *
 * Implements ArrayAccess against the legacy key names purely for backward compatibility, so code
 * written against the old raw usage array (for example `$response->usage['input_tokens']`) keeps
 * working. New code should read the typed properties, or the getters on Response/EmbeddingResponse.
 *
 * @implements ArrayAccess<string, int|mixed>
 */
final class Usage implements ArrayAccess
{
    public readonly int $totalTokens;

    /**
     * @param int                  $inputTokens  Prompt/input tokens
     * @param int                  $outputTokens Completion/output tokens
     * @param int|null             $totalTokens  Total tokens; defaults to inputTokens + outputTokens
     * @param array<string, mixed> $raw          The original provider usage payload, preserved verbatim
     */
    public function __construct(
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        ?int $totalTokens = null,
        private readonly array $raw = [],
    ) {
        $this->totalTokens = $totalTokens ?? ($this->inputTokens + $this->outputTokens);
    }

    /**
     * Build from a raw provider usage array, mapping the known key aliases.
     *
     * @param array<string, mixed> $usage Provider usage payload in any of the known key styles
     */
    public static function fromArray(array $usage): self
    {
        $input = $usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0;
        $output = $usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0;
        $total = $usage['total_tokens'] ?? null;

        return new self(
            (int) $input,
            (int) $output,
            $total !== null ? (int) $total : null,
            $usage,
        );
    }

    /**
     * The original provider usage payload, preserved verbatim.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @deprecated Legacy array-access shim; read the typed properties (inputTokens, etc.) instead.
     */
    public function offsetExists(mixed $offset): bool
    {
        return match ($offset) {
            'input_tokens', 'prompt_tokens', 'output_tokens', 'completion_tokens', 'total_tokens' => true,
            default => isset($this->raw[$offset]),
        };
    }

    /**
     * @deprecated Legacy array-access shim; read the typed properties (inputTokens, etc.) instead.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'input_tokens', 'prompt_tokens' => $this->inputTokens,
            'output_tokens', 'completion_tokens' => $this->outputTokens,
            'total_tokens' => $this->totalTokens,
            default => $this->raw[$offset] ?? null,
        };
    }

    /**
     * @deprecated Usage is immutable; this shim only exists to satisfy ArrayAccess.
     *
     * @throws LogicException Always, because Usage is immutable
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Usage is immutable.');
    }

    /**
     * @deprecated Usage is immutable; this shim only exists to satisfy ArrayAccess.
     *
     * @throws LogicException Always, because Usage is immutable
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Usage is immutable.');
    }
}
