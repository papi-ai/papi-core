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

use PapiAI\Core\Contracts\TokenEstimatorInterface;

/**
 * Zero-dependency token estimator using the familiar bytes-per-token rule of thumb.
 *
 * The unit is **bytes**, not characters: `strlen()` divided by a fixed ratio (4 by default,
 * roughly right for English prose and source code under the common BPE vocabularies). That choice
 * is deliberate rather than incidental. A UTF-8 character can span several bytes, so multibyte
 * text estimates high, and erring high is the safe direction for anything spending a budget: a
 * caller under-fills the context window instead of blowing past it.
 *
 * Naming the unit once, here, also keeps every estimate in the ecosystem comparable. Use it
 * wherever an approximate figure is enough (ingestion budgets, deciding whether a payload is worth
 * compressing) and swap in a real tokenizer when exact counts matter.
 */
final class HeuristicTokenEstimator implements TokenEstimatorInterface
{
    /**
     * @param positive-int $bytesPerToken Average bytes each token is assumed to consume
     */
    public function __construct(
        private readonly int $bytesPerToken = 4,
    ) {
    }

    public function estimateTokens(string $content): int
    {
        return (int) ceil(strlen($content) / $this->bytesPerToken);
    }
}
