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
 * Where a workflow run stands.
 */
enum Status: string
{
    /** A step is running, or the process running it died. */
    case Running = 'running';

    /** Every step ran, or a step finished the run early. */
    case Completed = 'completed';

    /** A step asked to wait; resume() continues with the answer. */
    case Paused = 'paused';

    /** A step threw; resume() runs it again. */
    case Failed = 'failed';

    /** A step was about to run more often than it may. The work done so far is in the state. */
    case LimitReached = 'limit_reached';

    public function canResume(): bool
    {
        return $this === self::Paused || $this === self::Failed;
    }
}
