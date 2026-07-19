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

use PapiAI\Core\OptimisationResult;

/**
 * Contract for proxies that reduce the number of tokens a payload will consume.
 *
 * Tool and command output is often the largest, noisiest contributor to an agent's context.
 * An optimisation proxy compresses that text before it reaches the model — stripping padding,
 * deduplicating, and summarising — so the same information costs fewer tokens.
 *
 * The reference implementation is an RTK adapter (https://github.com/rtk-ai/rtk), which shells
 * out to the `rtk` binary; other strategies (tokenizer-based pruning, summarisation) can
 * implement the same contract.
 */
interface LLMTokenOptimisationProxyInterface
{
    /**
     * Optimise a block of text (e.g. captured tool output) before it enters the context.
     *
     * @param string $content The raw text to compress
     * @param array{
     *     filter?: string,
     *     ultraCompact?: bool,
     * } $options Strategy-specific options (filter = named RTK filter such as grep, git-log)
     *
     * @return OptimisationResult The optimised text plus before/after token estimates
     */
    public function optimise(string $content, array $options = []): OptimisationResult;

    /**
     * Run a command through the proxy and return its optimised output.
     *
     * Intended for read-only developer commands (git, grep, ls, test runners) whose verbose
     * output is the real token cost. Implementations may execute the command to measure the
     * saving, so only pass side-effect-free commands.
     *
     * @param string $command The command line to run (e.g. "git status")
     * @param array{
     *     ultraCompact?: bool,
     * } $options Strategy-specific options
     *
     * @return OptimisationResult The optimised output plus before/after token estimates
     */
    public function optimiseCommand(string $command, array $options = []): OptimisationResult;

    /**
     * Estimate the number of tokens a block of text would consume.
     *
     * A cheap heuristic used to decide whether a payload is large enough to be worth optimising;
     * it does not call a real tokenizer.
     *
     * @param string $content The text to measure
     *
     * @return int The estimated token count
     */
    public function estimateTokens(string $content): int;
}
