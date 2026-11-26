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

namespace PapiAI\Core\Exception;

use PapiAI\Core\Usage;

/**
 * Agents kept handing a conversation to one another until the limit for one reply was reached,
 * and none of them answered. The conversation is left as it was before the reply.
 */
final class HandoffLimitException extends PapiException
{
    /**
     * @param int    $limit     The handoffs one reply may follow
     * @param string $refusedTo The agent the next handoff asked for
     * @param Usage  $usage     What the agents spent before the limit
     */
    public function __construct(
        public readonly int $limit,
        public readonly string $refusedTo,
        public readonly Usage $usage,
    ) {
        parent::__construct(sprintf('The conversation was handed over %d times in one reply and then once more, to "%s", without an answer.', $limit, $refusedTo));
    }
}
