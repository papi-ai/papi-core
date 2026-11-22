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

namespace PapiAI\Core\Workflow;

/**
 * What a step returns to continue at a named step instead of the next one.
 */
final class JumpTo
{
    /**
     * @param string $step   The step to continue at
     * @param mixed  $output Stored as the output of the step that jumps
     */
    public function __construct(
        public readonly string $step,
        public readonly mixed $output = null,
    ) {
    }
}
