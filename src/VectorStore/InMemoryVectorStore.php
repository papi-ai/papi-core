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

namespace PapiAI\Core\VectorStore;

use PapiAI\Core\Contracts\VectorStoreInterface;
use PapiAI\Core\SearchResult;

/**
 * In-memory vector store using brute-force cosine similarity search.
 *
 * Suitable for development, testing, and small datasets. All data is lost
 * when the process ends. For production use, prefer a dedicated vector database.
 */
final class InMemoryVectorStore implements VectorStoreInterface
{
    /** @var array<string, array{vector: array<float>, metadata: array<string, mixed>, content: ?string}> */
    private array $store = [];

    /** {@inheritDoc} */
    public function upsert(string $id, array $vector, array $metadata = [], ?string $content = null): void
    {
        $this->store[$id] = [
            'vector' => $vector,
            'metadata' => $metadata,
            'content' => $content,
        ];
    }

    /** {@inheritDoc} */
    public function query(array $vector, int $topK = 5, array $filter = []): array
    {
        $results = [];

        foreach ($this->store as $id => $entry) {
            if (!$this->matchesFilter($entry['metadata'], $filter)) {
                continue;
            }

            $score = $this->cosineSimilarity($vector, $entry['vector']);

            $results[] = new SearchResult(
                id: $id,
                score: $score,
                metadata: $entry['metadata'],
                content: $entry['content'],
            );
        }

        usort($results, fn (SearchResult $a, SearchResult $b) => $b->score <=> $a->score);

        return array_slice($results, 0, $topK);
    }

    /** {@inheritDoc} */
    public function delete(string $id): void
    {
        unset($this->store[$id]);
    }

    /** {@inheritDoc} */
    public function flush(): void
    {
        $this->store = [];
    }

    /** {@inheritDoc} */
    public function count(): int
    {
        return count($this->store);
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param array<float> $a
     * @param array<float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);

        if ($denominator === 0.0) {
            return 0.0;
        }

        return $dotProduct / $denominator;
    }

    /**
     * Check if metadata matches all filter criteria.
     *
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filter
     */
    private function matchesFilter(array $metadata, array $filter): bool
    {
        foreach ($filter as $key => $value) {
            if (!array_key_exists($key, $metadata) || $metadata[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
