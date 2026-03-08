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
 * Estimated cost for an API call based on token usage and model pricing.
 */
final class CostEstimate
{
    /**
     * @param float $inputCost Cost for input tokens
     * @param float $outputCost Cost for output tokens
     * @param string $currency Currency code (default: USD)
     */
    public function __construct(
        public readonly float $inputCost,
        public readonly float $outputCost,
        public readonly string $currency = 'USD',
    ) {
    }

    /**
     * Get the total estimated cost (input + output).
     *
     * @return float Combined cost in the specified currency
     */
    public function total(): float
    {
        return $this->inputCost + $this->outputCost;
    }

    /**
     * Estimate cost from token usage and per-million-token pricing.
     *
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @param float $inputPricePerMillion Price per 1M input tokens
     * @param float $outputPricePerMillion Price per 1M output tokens
     *
     * @return self The calculated cost estimate
     */
    public static function fromUsage(
        int $inputTokens,
        int $outputTokens,
        float $inputPricePerMillion,
        float $outputPricePerMillion,
    ): self {
        return new self(
            inputCost: ($inputTokens / 1_000_000) * $inputPricePerMillion,
            outputCost: ($outputTokens / 1_000_000) * $outputPricePerMillion,
        );
    }
}
