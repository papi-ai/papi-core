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
 * Immutable value object containing embedding vectors returned by an embedding provider.
 *
 * Wraps one or more embedding vectors along with the model used and token usage,
 * providing convenience methods for single-vector access and dimensionality inspection.
 */
final class EmbeddingResponse
{
    /**
     * Token usage for the request.
     *
     * Typed as the neutral Usage value object; it is still array-accessible
     * (`$response->usage['prompt_tokens']`) for backward compatibility.
     */
    public readonly Usage $usage;

    /**
     * @param array<array<float>> $embeddings The embedding vectors
     * @param string              $model      The model used
     * @param Usage|array         $usage      Token usage (a raw provider array is accepted and normalised)
     */
    public function __construct(
        public readonly array $embeddings,
        public readonly string $model,
        Usage|array $usage = [],
    ) {
        $this->usage = is_array($usage) ? Usage::fromArray($usage) : $usage;
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
     * Get the number of embeddings in this response.
     *
     * @return int Number of embedding vectors
     */
    public function count(): int
    {
        return count($this->embeddings);
    }

    /**
     * Get the dimensionality (vector length) of the embeddings.
     *
     * @return int Number of dimensions per vector
     */
    public function dimensions(): int
    {
        return count($this->first());
    }

    /**
     * Get the number of prompt tokens consumed.
     *
     * @return int Prompt token count, or 0 if unavailable
     */
    public function getPromptTokens(): int
    {
        return $this->usage->inputTokens;
    }

    /**
     * Get the total token count for this embedding request.
     *
     * @return int Total token count, or 0 if unavailable
     */
    public function getTotalTokens(): int
    {
        return $this->usage->totalTokens;
    }
}
