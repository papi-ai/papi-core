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
 * Command output is often the largest, noisiest contributor to an agent's context. An
 * optimisation proxy compresses that text before it reaches the model (stripping padding,
 * deduplicating, summarising) so the same information costs fewer tokens.
 *
 * **Optimisation is lossy by design.** Implementations rewrite the text and may drop detail the
 * caller considers incidental, such as file names in a directory listing. Only route text through
 * a proxy when the model needs to *read* it. Never route content the model must reproduce
 * verbatim (source files, whole-file edit payloads, anything round-tripped back to disk): at best
 * the proxy returns it unchanged for no gain, at worst it corrupts it. Text that is already
 * structured or summarised at the source has nothing left to squeeze either.
 *
 * The reference implementation is an RTK adapter (https://github.com/rtk-ai/rtk), which shells
 * out to the `rtk` binary; other strategies (tokenizer-based pruning, summarisation) can
 * implement the same contract.
 *
 * Nothing in papi wires an optimiser in automatically. Agent middleware only sees the request
 * prompt (tool results are resolved inside the run loop), so the integration point is your own
 * tool handler: run the command, pipe its output through the proxy, return the compressed text.
 */
interface LLMTokenOptimisationProxyInterface extends TokenEstimatorInterface
{
    /**
     * Optimise a block of text (e.g. captured command output) before it enters the context.
     *
     * Lossy: see the class docblock for what is safe to pass.
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
     * output is the real token cost. Implementations measure the saving by also running the
     * command unoptimised, so pass only side-effect-free commands, and pass `measure: false` to
     * skip that second execution when the saving figure is not worth the latency (the result's
     * `tokensBefore` is then null).
     *
     * @param string $command The command line to run (e.g. "git status")
     * @param array{
     *     measure?: bool,
     *     ultraCompact?: bool,
     * } $options Strategy-specific options
     *
     * @return OptimisationResult The optimised output plus before/after token estimates
     */
    public function optimiseCommand(string $command, array $options = []): OptimisationResult;
}
