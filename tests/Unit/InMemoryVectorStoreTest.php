<?php

declare(strict_types=1);

use PapiAI\Core\Contracts\VectorStoreInterface;
use PapiAI\Core\SearchResult;
use PapiAI\Core\VectorStore\InMemoryVectorStore;

describe('InMemoryVectorStore', function () {
    beforeEach(function () {
        $this->store = new InMemoryVectorStore();
    });

    describe('construction', function () {
        it('implements VectorStoreInterface', function () {
            expect($this->store)->toBeInstanceOf(VectorStoreInterface::class);
        });

        it('starts empty', function () {
            expect($this->store->count())->toBe(0);
        });
    });

    describe('upsert', function () {
        it('adds a vector', function () {
            $this->store->upsert('doc-1', [1.0, 0.0, 0.0]);

            expect($this->store->count())->toBe(1);
        });

        it('updates an existing vector', function () {
            $this->store->upsert('doc-1', [1.0, 0.0], ['v' => 1]);
            $this->store->upsert('doc-1', [0.0, 1.0], ['v' => 2]);

            expect($this->store->count())->toBe(1);

            $results = $this->store->query([0.0, 1.0], 1);
            expect($results[0]->metadata['v'])->toBe(2);
        });

        it('stores content alongside vector', function () {
            $this->store->upsert('doc-1', [1.0, 0.0], [], 'Hello world');

            $results = $this->store->query([1.0, 0.0], 1);
            expect($results[0]->content)->toBe('Hello world');
        });
    });

    describe('query', function () {
        beforeEach(function () {
            // Three orthogonal-ish vectors
            $this->store->upsert('php', [1.0, 0.0, 0.0], ['lang' => 'php'], 'PHP is great');
            $this->store->upsert('python', [0.0, 1.0, 0.0], ['lang' => 'python'], 'Python is popular');
            $this->store->upsert('rust', [0.0, 0.0, 1.0], ['lang' => 'rust'], 'Rust is fast');
        });

        it('returns results sorted by similarity', function () {
            $results = $this->store->query([1.0, 0.1, 0.0]);

            expect($results)->toHaveCount(3);
            expect($results[0]->id)->toBe('php');
            expect($results[0])->toBeInstanceOf(SearchResult::class);
        });

        it('respects topK limit', function () {
            $results = $this->store->query([1.0, 0.0, 0.0], 2);

            expect($results)->toHaveCount(2);
        });

        it('returns high similarity for matching vectors', function () {
            $results = $this->store->query([1.0, 0.0, 0.0], 1);

            expect($results[0]->score)->toBeGreaterThan(0.99);
        });

        it('returns low similarity for orthogonal vectors', function () {
            $results = $this->store->query([1.0, 0.0, 0.0], 3);
            $rustResult = array_values(array_filter($results, fn ($r) => $r->id === 'rust'))[0];

            expect($rustResult->score)->toBeLessThan(0.01);
        });

        it('filters by metadata', function () {
            $results = $this->store->query([0.5, 0.5, 0.5], 10, ['lang' => 'php']);

            expect($results)->toHaveCount(1);
            expect($results[0]->id)->toBe('php');
        });

        it('returns empty when filter matches nothing', function () {
            $results = $this->store->query([1.0, 0.0, 0.0], 10, ['lang' => 'java']);

            expect($results)->toBe([]);
        });

        it('handles zero vectors gracefully', function () {
            $this->store->upsert('zero', [0.0, 0.0, 0.0]);

            $results = $this->store->query([0.0, 0.0, 0.0]);
            $zeroResult = array_values(array_filter($results, fn ($r) => $r->id === 'zero'))[0];

            expect($zeroResult->score)->toBe(0.0);
        });
    });

    describe('delete', function () {
        it('removes a vector by ID', function () {
            $this->store->upsert('doc-1', [1.0, 0.0]);
            $this->store->upsert('doc-2', [0.0, 1.0]);

            $this->store->delete('doc-1');

            expect($this->store->count())->toBe(1);
            $results = $this->store->query([1.0, 0.0], 10);
            expect($results)->toHaveCount(1);
            expect($results[0]->id)->toBe('doc-2');
        });

        it('does nothing for non-existent ID', function () {
            $this->store->upsert('doc-1', [1.0]);
            $this->store->delete('doc-999');

            expect($this->store->count())->toBe(1);
        });
    });

    describe('flush', function () {
        it('removes all vectors', function () {
            $this->store->upsert('doc-1', [1.0]);
            $this->store->upsert('doc-2', [0.0]);

            $this->store->flush();

            expect($this->store->count())->toBe(0);
            expect($this->store->query([1.0]))->toBe([]);
        });
    });
});
