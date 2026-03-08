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

use PapiAI\Core\SearchResult;

/**
 * Contract for vector storage backends used in similarity search and RAG.
 *
 * Implementations store embedding vectors alongside metadata and content,
 * enabling efficient nearest-neighbor queries for semantic retrieval.
 */
interface VectorStoreInterface
{
    /**
     * Insert or update a vector with metadata.
     *
     * @param string $id Unique document identifier
     * @param array<float> $vector The embedding vector
     * @param array<string, mixed> $metadata Associated metadata
     * @param string|null $content Original text content to store alongside
     */
    public function upsert(string $id, array $vector, array $metadata = [], ?string $content = null): void;

    /**
     * Query for similar vectors.
     *
     * @param array<float> $vector The query vector
     * @param int $topK Maximum number of results
     * @param array<string, mixed> $filter Metadata filter criteria
     * @return array<SearchResult>
     */
    public function query(array $vector, int $topK = 5, array $filter = []): array;

    /**
     * Delete a vector by ID.
     *
     * @param string $id The document identifier to remove
     */
    public function delete(string $id): void;

    /**
     * Flush all vectors from the store.
     */
    public function flush(): void;

    /**
     * Get the number of stored vectors.
     *
     * @return int Total number of vectors in the store
     */
    public function count(): int;
}
