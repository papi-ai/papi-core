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

use PapiAI\Core\EmbeddingResponse;

/**
 * Contract for providers that generate text embeddings (vector representations).
 *
 * Used to convert text into numerical vectors for semantic search, clustering,
 * and retrieval-augmented generation (RAG) workflows.
 */
interface EmbeddingProviderInterface
{
    /**
     * Generate embeddings for the given input(s).
     *
     * @param string|array<string> $input One or more texts to embed
     * @param array{
     *     model?: string,
     *     dimensions?: int,
     * } $options Provider-specific options
     *
     * @return EmbeddingResponse The embedding vectors with usage statistics
     */
    public function embed(string|array $input, array $options = []): EmbeddingResponse;
}
