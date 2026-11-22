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

namespace PapiAI\Core\Workflow\Step;

use Closure;
use PapiAI\Core\Workflow\Finish;
use PapiAI\Core\Workflow\JumpTo;
use PapiAI\Core\Workflow\Pause;
use PapiAI\Core\Workflow\State;

/**
 * A step that is one call of ordinary PHP, run at most a set number of times in a run.
 *
 * @internal
 */
final class CallableStep implements StepInterface
{
    /**
     * @param Closure(State): mixed $step
     */
    public function __construct(
        private readonly string $name,
        private readonly Closure $step,
        private readonly int $atMost,
    ) {
    }

    public function run(State $state, ?string $following): State
    {
        if ($state->visitsOf($this->name) >= $this->atMost) {
            return $state->limitReachedAt($this->name);
        }

        $state = $state->entering($this->name);
        $returned = ($this->step)($state);

        if ($returned instanceof Pause) {
            return $state->pausedAt($this->name, $returned->payload);
        }

        [$next, $produced] = match (true) {
            $returned instanceof JumpTo => [$returned->step, new Produced($returned->output)],
            $returned instanceof Finish => [null, new Produced($returned->output)],
            default => [$following, new Produced($returned)],
        };

        return $state->spending($this->name, $produced->usage())->withOutput($this->name, $produced->output(), $next);
    }
}
