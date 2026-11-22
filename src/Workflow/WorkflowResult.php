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

use PapiAI\Core\Usage;
use Throwable;

/**
 * How a run of a workflow ended, for now or for good.
 */
final class WorkflowResult
{
    /**
     * @param State          $state  Everything needed to store the run and resume it
     * @param mixed          $output What the last step to run produced
     * @param Usage          $usage  Tokens of every model call in every step, earlier runs of this state included
     * @param Throwable|null $error  What the failed step threw, for a worker to rethrow
     */
    public function __construct(
        public readonly Status $status,
        public readonly State $state,
        public readonly mixed $output,
        public readonly Usage $usage,
        public readonly ?Throwable $error = null,
    ) {
    }
}
