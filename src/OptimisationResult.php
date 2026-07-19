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
 * Immutable result of running content through a token-optimisation proxy.
 *
 * Holds the optimised text alongside the estimated token counts before and after, so callers
 * can decide whether the saving was worthwhile and report on it. Token counts are estimates,
 * not the output of a real tokenizer.
 */
final class OptimisationResult
{
    /**
     * @param string $optimised    The optimised (compressed) text
     * @param int    $tokensBefore Estimated tokens in the original content
     * @param int    $tokensAfter  Estimated tokens in the optimised content
     * @param string $strategy     Identifier of the strategy used (e.g. "rtk:pipe", "rtk:command")
     */
    public function __construct(
        public readonly string $optimised,
        public readonly int $tokensBefore,
        public readonly int $tokensAfter,
        public readonly string $strategy = '',
    ) {
    }

    /**
     * Number of tokens saved (never negative).
     *
     * @return int The estimated tokens saved
     */
    public function tokensSaved(): int
    {
        return max(0, $this->tokensBefore - $this->tokensAfter);
    }

    /**
     * Percentage of tokens saved, rounded to one decimal place.
     *
     * @return float The saving as a percentage, or 0.0 when there was nothing to save
     */
    public function savingsPercent(): float
    {
        if ($this->tokensBefore <= 0) {
            return 0.0;
        }

        return round($this->tokensSaved() / $this->tokensBefore * 100, 1);
    }
}
