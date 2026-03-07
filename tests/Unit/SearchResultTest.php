<?php

declare(strict_types=1);

use PapiAI\Core\SearchResult;

describe('SearchResult', function () {
    it('stores id, score, metadata, and content', function () {
        $result = new SearchResult(
            id: 'doc-1',
            score: 0.95,
            metadata: ['category' => 'tech'],
            content: 'PHP is great',
        );

        expect($result->id)->toBe('doc-1');
        expect($result->score)->toBe(0.95);
        expect($result->metadata)->toBe(['category' => 'tech']);
        expect($result->content)->toBe('PHP is great');
    });

    it('defaults content to null', function () {
        $result = new SearchResult(id: 'doc-1', score: 0.5);

        expect($result->content)->toBeNull();
        expect($result->metadata)->toBe([]);
    });
});
