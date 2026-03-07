<?php

declare(strict_types=1);

use PapiAI\Core\CostEstimate;

describe('CostEstimate', function () {
    it('calculates total cost', function () {
        $cost = new CostEstimate(inputCost: 0.01, outputCost: 0.03);

        expect($cost->total())->toBe(0.04);
        expect($cost->currency)->toBe('USD');
    });

    it('estimates from token usage', function () {
        // GPT-4o pricing: $2.50/1M input, $10/1M output
        $cost = CostEstimate::fromUsage(
            inputTokens: 1000,
            outputTokens: 500,
            inputPricePerMillion: 2.50,
            outputPricePerMillion: 10.00,
        );

        expect($cost->inputCost)->toBe(0.0025);
        expect($cost->outputCost)->toBe(0.005);
        expect($cost->total())->toBe(0.0075);
    });

    it('handles zero tokens', function () {
        $cost = CostEstimate::fromUsage(
            inputTokens: 0,
            outputTokens: 0,
            inputPricePerMillion: 2.50,
            outputPricePerMillion: 10.00,
        );

        expect($cost->total())->toBe(0.0);
    });
});
