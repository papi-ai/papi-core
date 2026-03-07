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

final class EmbeddingResponse
{
    /**
     * @param array<array<float>> $embeddings The embedding vectors
     * @param string $model The model used
     * @param array{prompt_tokens?: int, total_tokens?: int} $usage Token usage
     */
    public function __construct(
        public readonly array $embeddings,
        public readonly string $model,
        public readonly array $usage = [],
    ) {
    }

    /**
     * Get the first embedding vector (convenience for single-input requests).
     *
     * @return array<float>
     */
    public function first(): array
    {
        return $this->embeddings[0] ?? [];
    }

    /**
     * Get the number of embeddings.
     */
    public function count(): int
    {
        return count($this->embeddings);
    }

    /**
     * Get the dimensionality of the embeddings.
     */
    public function dimensions(): int
    {
        return count($this->first());
    }

    /**
     * Get prompt token count.
     */
    public function getPromptTokens(): int
    {
        return $this->usage['prompt_tokens'] ?? 0;
    }

    /**
     * Get total token count.
     */
    public function getTotalTokens(): int
    {
        return $this->usage['total_tokens'] ?? 0;
    }
}
