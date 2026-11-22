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

use Closure;
use PapiAI\Core\Contracts\WorkflowStoreInterface;
use PapiAI\Core\Workflow\Step\CallableStep;
use PapiAI\Core\Workflow\Step\EachStep;
use PapiAI\Core\Workflow\Step\StepInterface;
use Throwable;

/**
 * A named list of steps, run in order over a State that is plain data.
 *
 * A step is ordinary PHP: `fn (State $state) => ...`. It may run an agent, call a provider or
 * make no model call at all. What it returns decides what happens next:
 *
 * - a Response: its data, or its text, becomes the step's output and its usage is counted;
 * - any plain value: becomes the step's output;
 * - `new JumpTo('step')`: continues at the named step;
 * - `new Pause($payload)`: stops the run until resume() is called with an answer;
 * - `new Finish($output)`: ends the run as completed.
 *
 * A step that throws ends the run as failed, and resume() runs that step again. With a store, the
 * run is saved after every step, so it can be resumed from another process.
 */
final class Workflow
{
    public const DEFAULT_LIMIT = 25;

    private const HOOKS = ['beforeStep', 'afterStep', 'onStepFailed', 'onPause'];

    /** @var array<string, StepInterface> */
    private array $steps = [];

    /** @var array<string, Closure> */
    private array $hooks = [];

    public function __construct(
        private readonly string $name,
        private readonly ?WorkflowStoreInterface $store = null,
    ) {
    }

    /**
     * Add a step.
     *
     * @param callable(State): mixed $step
     * @param int|null               $atMost How often the step may run in one run, DEFAULT_LIMIT when null.
     *   Reaching it ends the run as Status::LimitReached, with the work done so far in the state.
     *
     * @throws WorkflowException When the name is empty or taken
     */
    public function step(string $name, callable $step, ?int $atMost = null): self
    {
        return $this->add($name, new CallableStep($name, $step(...), $atMost ?? self::DEFAULT_LIMIT));
    }

    /**
     * Add a step that works through a list, saving the run after every item. Its output is the
     * list of results, in order. A failure resumes at the item it happened on.
     *
     * @param callable(State): array<mixed> $items The list, asked for once per run
     * @param callable(mixed, State): mixed $step  Handles one item; a Response gives its data or text
     *
     * @throws WorkflowException When the name is empty or taken
     */
    public function each(string $name, callable $items, callable $step): self
    {
        return $this->add($name, new EachStep($name, $items(...), $step(...)));
    }

    /**
     * Add a step that runs one of several branches.
     *
     * @param callable(State): mixed                $choose    Gives the key of the branch to run
     * @param array<array-key, callable(State): mixed> $branches Steps by key
     * @param callable(State): mixed                $otherwise Runs when the key names no branch
     *
     * @throws WorkflowException When the name is empty or taken
     */
    public function branch(string $name, callable $choose, array $branches, callable $otherwise): self
    {
        return $this->step($name, static function (State $state) use ($choose, $branches, $otherwise): mixed {
            $key = $choose($state);
            $branch = (is_string($key) || is_int($key)) && isset($branches[$key]) ? $branches[$key] : $otherwise;

            return $branch($state);
        });
    }

    /**
     * Be told as the run progresses, for a status page or server-sent events.
     *
     * - beforeStep(string $step, State $state)
     * - afterStep(string $step, State $state)
     * - onStepFailed(string $step, Throwable $error, State $state)
     * - onPause(string $step, mixed $payload, State $state)
     *
     * @throws WorkflowException When the hook name is not one of those
     */
    public function hook(string $name, Closure $callback): self
    {
        if (!in_array($name, self::HOOKS, true)) {
            throw new WorkflowException(sprintf('Workflows have no "%s" hook. They have: %s.', $name, implode(', ', self::HOOKS)));
        }

        $this->hooks[$name] = $callback;

        return $this;
    }

    /**
     * Start a run.
     *
     * @param array<string, mixed> $input Plain data the steps read with State::get()
     * @param string|null          $id    The run's ID, such as a job ID; a random one when null
     *
     * @throws WorkflowException When the workflow has no steps, the input is not plain data, or the
     *   store already holds a run with that ID
     */
    public function start(array $input, ?string $id = null): WorkflowResult
    {
        if ($this->steps === []) {
            throw new WorkflowException(sprintf('Workflow "%s" has no steps.', $this->name));
        }

        $id ??= bin2hex(random_bytes(16));

        if ($this->store?->load($id) !== null) {
            throw new WorkflowException(sprintf('A run "%s" is already stored. Resume it, or start under another ID.', $id));
        }

        return $this->runFrom((new State($id, $this->name, values: $input))->startingAt(array_key_first($this->steps)));
    }

    /**
     * Continue a paused or failed run: a paused step takes the answer as its output and the run
     * moves on, a failed step runs again. Steps that finished are not run again.
     *
     * @param string|State $run           The run, or its ID in the store
     * @param mixed        $answer        Plain data for the paused step
     * @param bool         $evenIfRunning Also continue a run left running by a process that died
     *
     * @throws WorkflowException When the run is unknown, belongs to another workflow, is not paused
     *   or failed, or waits at a step this workflow no longer has
     */
    public function resume(string|State $run, mixed $answer = null, bool $evenIfRunning = false): WorkflowResult
    {
        $state = $this->resumable(is_string($run) ? $this->stored($run) : $run, $evenIfRunning);
        $step = (string) $state->nextStep();

        if ($state->status === Status::Paused) {
            return $this->runFrom($state->withOutput($step, $answer, $this->following($step)));
        }

        return $this->runFrom($state->startingAt($step));
    }

    private function add(string $name, StepInterface $step): self
    {
        if ($name === '' || isset($this->steps[$name])) {
            throw new WorkflowException(sprintf('Workflow "%s" needs a unique, non-empty name for each step; "%s" will not do.', $this->name, $name));
        }

        $this->steps[$name] = $step;

        return $this;
    }

    private function stored(string $id): State
    {
        if ($this->store === null) {
            throw new WorkflowException(sprintf('Workflow "%s" has no store to load run "%s" from. Pass the State instead.', $this->name, $id));
        }

        return $this->store->load($id) ?? throw new WorkflowException(sprintf('No run "%s" is stored.', $id));
    }

    private function resumable(State $state, bool $evenIfRunning): State
    {
        if ($state->workflow !== $this->name) {
            throw new WorkflowException(sprintf('Run "%s" belongs to workflow "%s", not "%s".', $state->id, $state->workflow, $this->name));
        }

        if (!$state->status->canResume() && !($evenIfRunning && $state->status === Status::Running)) {
            throw new WorkflowException(sprintf('Run "%s" is %s, so there is nothing to resume.', $state->id, $state->status->value));
        }

        $this->stepNamed((string) $state->nextStep(), $state);

        return $state;
    }

    private function runFrom(State $state): WorkflowResult
    {
        $this->store?->save($state);
        $error = null;

        while ($state->status === Status::Running && $state->nextStep() !== null) {
            $name = $state->nextStep();
            $step = $this->stepNamed($name, $state);
            $this->tell('beforeStep', $name, $state);

            try {
                $state = $step->run($state, $this->following($name));
            } catch (WorkflowException $misuse) {
                throw $misuse;
            } catch (Throwable $error) {
                $state = $state->failedAt($name, $error->getMessage());
            }

            $this->store?->save($state);
            $this->tellHow($name, $state, $error);
        }

        return new WorkflowResult($state->status, $state, $state->lastOutput(), $state->usage(), $error);
    }

    private function tellHow(string $step, State $state, ?Throwable $error): void
    {
        match ($state->status) {
            Status::Failed => $this->tell('onStepFailed', $step, $error, $state),
            Status::Paused => $this->tell('onPause', $step, $state->pausedFor(), $state),
            Status::LimitReached => null,
            default => $this->tell('afterStep', $step, $state),
        };
    }

    private function tell(string $hook, mixed ...$arguments): void
    {
        if (isset($this->hooks[$hook])) {
            ($this->hooks[$hook])(...$arguments);
        }
    }

    private function stepNamed(string $name, State $state): StepInterface
    {
        return $this->steps[$name] ?? throw new WorkflowException(sprintf(
            'Run "%s" is to continue at step "%s", which workflow "%s" does not have. It has: %s.',
            $state->id,
            $name,
            $this->name,
            implode(', ', array_map(static fn (string $step): string => '"' . $step . '"', array_keys($this->steps))),
        ));
    }

    private function following(string $step): ?string
    {
        $names = array_keys($this->steps);
        $position = array_search($step, $names, true);

        return $position === false ? null : $names[$position + 1] ?? null;
    }
}
