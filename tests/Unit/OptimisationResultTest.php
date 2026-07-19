<?php

declare(strict_types=1);

use PapiAI\Core\OptimisationResult;

describe('OptimisationResult', function () {
    it('stores the optimised text, token counts, and strategy', function () {
        $result = new OptimisationResult(
            optimised: 'compact',
            tokensBefore: 100,
            tokensAfter: 40,
            strategy: 'rtk:pipe',
        );

        expect($result->optimised)->toBe('compact');
        expect($result->tokensBefore)->toBe(100);
        expect($result->tokensAfter)->toBe(40);
        expect($result->strategy)->toBe('rtk:pipe');
    });

    it('computes tokens saved', function () {
        $result = new OptimisationResult('x', 100, 40);

        expect($result->tokensSaved())->toBe(60);
    });

    it('never reports negative savings', function () {
        $result = new OptimisationResult('x', 40, 100);

        expect($result->tokensSaved())->toBe(0);
        expect($result->savingsPercent())->toBe(0.0);
    });

    it('computes savings percentage', function () {
        $result = new OptimisationResult('x', 200, 50);

        expect($result->savingsPercent())->toBe(75.0);
    });

    it('returns zero percent when there is nothing to measure', function () {
        $result = new OptimisationResult('', 0, 0);

        expect($result->savingsPercent())->toBe(0.0);
    });
});
