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
use PapiAI\Core\Workflow\WorkflowException;

/**
 * A step that works through a list one item per unit of work, so the run is saved after every
 * item and a failure costs only the item it happened on. Its output is the list of results.
 *
 * @internal
 */
final class EachStep implements StepInterface
{
    /**
     * @param Closure(State): mixed        $items The list to work through, asked for once per run
     * @param Closure(mixed, State): mixed $step  Handles one item
     */
    public function __construct(
        private readonly string $name,
        private readonly Closure $items,
        private readonly Closure $step,
    ) {
    }

    public function run(State $state, ?string $following): State
    {
        if (!$state->isWorkingThrough($this->name)) {
            return $this->startedOn($state);
        }

        if ($state->hasItemLeft($this->name)) {
            $state = $this->withNextItemDone($state);
        }

        if ($state->hasItemLeft($this->name)) {
            return $state;
        }

        return $state->withOutput($this->name, $state->itemsDone($this->name), $following);
    }

    private function startedOn(State $state): State
    {
        $items = ($this->items)($state);

        if (!is_array($items)) {
            throw new WorkflowException(sprintf('Step "%s" needs a list to work through, but got %s.', $this->name, get_debug_type($items)));
        }

        return $state->entering($this->name)->workingThrough($this->name, array_values($items));
    }

    private function withNextItemDone(State $state): State
    {
        $returned = ($this->step)($state->itemLeft($this->name), $state);

        if ($returned instanceof Pause || $returned instanceof JumpTo || $returned instanceof Finish) {
            throw new WorkflowException(sprintf(
                'Step "%s" works through a list, so an item cannot return %s. Do that in a step of its own.',
                $this->name,
                get_debug_type($returned),
            ));
        }

        $produced = new Produced($returned);

        return $state->spending($this->name, $produced->usage())->withItemDone($this->name, $produced->output());
    }
}
