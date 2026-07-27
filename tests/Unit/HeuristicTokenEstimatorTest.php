<?php

declare(strict_types=1);

use PapiAI\Core\Contracts\TokenEstimatorInterface;
use PapiAI\Core\HeuristicTokenEstimator;

describe('HeuristicTokenEstimator', function () {
    it('is a token estimator', function () {
        expect(new HeuristicTokenEstimator())->toBeInstanceOf(TokenEstimatorInterface::class);
    });

    it('estimates four bytes per token by default', function () {
        expect((new HeuristicTokenEstimator())->estimateTokens(str_repeat('a', 400)))->toBe(100);
    });

    it('rounds partial tokens up', function () {
        expect((new HeuristicTokenEstimator())->estimateTokens('abcde'))->toBe(2);
    });

    it('returns zero for empty content', function () {
        expect((new HeuristicTokenEstimator())->estimateTokens(''))->toBe(0);
    });

    it('accepts a different bytes-per-token ratio', function () {
        expect((new HeuristicTokenEstimator(2))->estimateTokens(str_repeat('a', 400)))->toBe(200);
    });

    it('counts bytes, so multibyte text estimates high', function () {
        // 3 characters but 9 bytes: a character-based rule of thumb would say 1 token, not 3.
        expect((new HeuristicTokenEstimator())->estimateTokens('日本語'))->toBe(3);
    });
});
