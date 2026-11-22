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
 * What a tool returns to pass the conversation to another agent. The Agent ends its run there,
 * and the Response names the agent to continue with.
 */
final class Handoff
{
    /**
     * @param string $to The name of the agent taking over
     */
    public function __construct(
        public readonly string $to,
    ) {
    }
}
