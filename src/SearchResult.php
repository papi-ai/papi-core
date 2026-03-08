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
 * Immutable value object representing a single result from a vector similarity search.
 *
 * Returned by VectorStoreInterface::query(), ranked by cosine similarity score.
 */
final class SearchResult
{
    /**
     * @param string $id The document identifier
     * @param float $score Similarity score (0.0 to 1.0, higher = more similar)
     * @param array<string, mixed> $metadata Associated metadata
     * @param string|null $content Original text content, if stored
     */
    public function __construct(
        public readonly string $id,
        public readonly float $score,
        public readonly array $metadata = [],
        public readonly ?string $content = null,
    ) {
    }
}
