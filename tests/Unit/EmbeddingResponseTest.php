<?php

declare(strict_types=1);

use PapiAI\Core\EmbeddingResponse;

describe('EmbeddingResponse', function () {
    describe('construction', function () {
        it('stores embeddings, model, and usage', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]],
                model: 'text-embedding-3-small',
                usage: ['prompt_tokens' => 10, 'total_tokens' => 10],
            );

            expect($response->embeddings)->toHaveCount(2);
            expect($response->model)->toBe('text-embedding-3-small');
        });
    });

    describe('first', function () {
        it('returns the first embedding vector', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1, 0.2], [0.3, 0.4]],
                model: 'test',
            );

            expect($response->first())->toBe([0.1, 0.2]);
        });

        it('returns empty array when no embeddings', function () {
            $response = new EmbeddingResponse(embeddings: [], model: 'test');

            expect($response->first())->toBe([]);
        });
    });

    describe('count', function () {
        it('returns the number of embeddings', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1], [0.2], [0.3]],
                model: 'test',
            );

            expect($response->count())->toBe(3);
        });
    });

    describe('dimensions', function () {
        it('returns the dimensionality', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1, 0.2, 0.3, 0.4]],
                model: 'test',
            );

            expect($response->dimensions())->toBe(4);
        });
    });

    describe('usage', function () {
        it('returns prompt tokens', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1]],
                model: 'test',
                usage: ['prompt_tokens' => 42, 'total_tokens' => 42],
            );

            expect($response->getPromptTokens())->toBe(42);
        });

        it('returns total tokens', function () {
            $response = new EmbeddingResponse(
                embeddings: [[0.1]],
                model: 'test',
                usage: ['prompt_tokens' => 42, 'total_tokens' => 42],
            );

            expect($response->getTotalTokens())->toBe(42);
        });

        it('returns zero when usage is empty', function () {
            $response = new EmbeddingResponse(embeddings: [[0.1]], model: 'test');

            expect($response->getPromptTokens())->toBe(0);
            expect($response->getTotalTokens())->toBe(0);
        });
    });
});
