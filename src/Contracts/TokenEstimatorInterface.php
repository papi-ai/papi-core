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

namespace PapiAI\Core\Contracts;

/**
 * Contract for anything that can put a token figure on a block of text.
 *
 * Deliberately a single method so components that only need to size a payload (ingestion budgets,
 * context windows, "is this worth compressing?" checks) depend on nothing more than that.
 * Richer contracts extend it: {@see LLMTokenOptimisationProxyInterface} is an estimator that can
 * also compress.
 *
 * Implementations may be heuristic or exact. {@see \PapiAI\Core\HeuristicTokenEstimator} is the
 * zero-dependency default; a real tokenizer satisfies the same contract with better numbers.
 */
interface TokenEstimatorInterface
{
    /**
     * Estimate the number of tokens a block of text would consume.
     *
     * @param string $content The text to measure
     *
     * @return int The estimated token count (never negative)
     */
    public function estimateTokens(string $content): int;
}
