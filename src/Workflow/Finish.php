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
 * What a step returns to end the run there, as completed.
 */
final class Finish
{
    /**
     * @param mixed $output Stored as the step's output, and the run's
     */
    public function __construct(
        public readonly mixed $output = null,
    ) {
    }
}
