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
 *
 * The baseline can be unknown: measuring it means processing (or running) the content twice, so
 * a caller may opt out. `$tokensBefore` is then null and the saving is unreportable rather than
 * reported as zero.
 */
final class OptimisationResult
{
    /**
     * @param string   $optimised    The optimised (compressed) text
     * @param int|null $tokensBefore Estimated tokens in the original content, or null if unmeasured
     * @param int      $tokensAfter  Estimated tokens in the optimised content
     * @param string   $strategy     Identifier of the strategy used (e.g. "rtk:pipe", "rtk:command")
     */
    public function __construct(
        public readonly string $optimised,
        public readonly ?int $tokensBefore,
        public readonly int $tokensAfter,
        public readonly string $strategy = '',
    ) {
    }

    /**
     * Whether the original content was measured, and so whether a saving can be reported.
     */
    public function isMeasured(): bool
    {
        return $this->tokensBefore !== null;
    }

    /**
     * Number of tokens saved (never negative).
     *
     * @return int|null The estimated tokens saved, or null when the baseline was not measured
     */
    public function tokensSaved(): ?int
    {
        if ($this->tokensBefore === null) {
            return null;
        }

        return max(0, $this->tokensBefore - $this->tokensAfter);
    }

    /**
     * Percentage of tokens saved, rounded to one decimal place.
     *
     * @return float|null The saving as a percentage, 0.0 when there was nothing to save, or null
     *                    when the baseline was not measured
     */
    public function savingsPercent(): ?float
    {
        if ($this->tokensBefore === null) {
            return null;
        }

        if ($this->tokensBefore <= 0) {
            return 0.0;
        }

        return round((int) $this->tokensSaved() / $this->tokensBefore * 100, 1);
    }
}
