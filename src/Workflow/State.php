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

/**
 * One run of a workflow, as plain data: what went in, what each step produced, where the run
 * stands and what it has cost. It survives toArray() and fromArray(), so it can cross a request,
 * a queue or a retry.
 *
 * A step reads it with get() and has(). Input keys and step outputs share one namespace, so a
 * step named like an input key replaces it. The methods that return a changed copy are for the
 * Workflow that runs the steps.
 */
final class State
{
    /**
     * @param array<string, mixed>                                              $values   Input, then step outputs by step name
     * @param string|null                                                       $next     The step to run next
     * @param string|null                                                       $last     The step whose output was stored last
     * @param array<string, int>                                                $visits   How often each step has run
     * @param array<string, Usage>                                              $usage    Tokens spent by each step
     * @param array<string, array{items: list<mixed>, results: list<mixed>}>   $progress Lists being worked through, by step
     * @param mixed                                                             $pause    What the paused step left for whoever answers
     * @param string|null                                                       $failure  What the failed step said
     */
    public function __construct(
        public readonly string $id,
        public readonly string $workflow,
        public readonly Status $status = Status::Running,
        private readonly array $values = [],
        private readonly ?string $next = null,
        private readonly ?string $last = null,
        private readonly array $visits = [],
        private readonly array $usage = [],
        private readonly array $progress = [],
        private readonly mixed $pause = null,
        private readonly ?string $failure = null,
    ) {
        $this->assertPlain($values, 'A workflow input');
    }

    /**
     * @param array<string, mixed> $data What toArray() gave
     *
     * @throws WorkflowException When the data is not a stored run
     */
    public static function fromArray(array $data): self
    {
        $status = Status::tryFrom((string) ($data['status'] ?? ''));

        if (!isset($data['id'], $data['workflow']) || $status === null) {
            throw new WorkflowException('This is not a stored workflow run: it needs an "id", a "workflow" and a known "status".');
        }

        return new self(
            id: (string) $data['id'],
            workflow: (string) $data['workflow'],
            status: $status,
            values: $data['values'] ?? [],
            next: $data['next'] ?? null,
            last: $data['last'] ?? null,
            visits: $data['visits'] ?? [],
            usage: array_map(static fn (array $usage): Usage => Usage::fromArray($usage), $data['usage'] ?? []),
            progress: $data['progress'] ?? [],
            pause: $data['pause'] ?? null,
            failure: $data['failure'] ?? null,
        );
    }

    /**
     * @return array<string, mixed> Arrays and scalars only
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'workflow' => $this->workflow,
            'status' => $this->status->value,
            'values' => $this->values,
            'next' => $this->next,
            'last' => $this->last,
            'visits' => $this->visits,
            'usage' => array_map(static fn (Usage $usage): array => $usage->toArray(), $this->usage),
            'progress' => $this->progress,
            'pause' => $this->pause,
            'failure' => $this->failure,
        ];
    }

    /**
     * An input value, or the output of the step with that name.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->values[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * The tokens of every model call so far, or of one step.
     */
    public function usage(?string $step = null): Usage
    {
        if ($step !== null) {
            return $this->usage[$step] ?? new Usage();
        }

        return array_reduce($this->usage, static fn (Usage $total, Usage $spent): Usage => $total->plus($spent), new Usage());
    }

    /**
     * What the paused step left for whoever answers.
     */
    public function pausedFor(): mixed
    {
        return $this->pause;
    }

    /**
     * What the failed step said, or null when the run has not failed.
     */
    public function failure(): ?string
    {
        return $this->failure;
    }

    public function nextStep(): ?string
    {
        return $this->next;
    }

    /**
     * What the last step to store an output produced.
     */
    public function lastOutput(): mixed
    {
        return $this->last === null ? null : $this->values[$this->last] ?? null;
    }

    public function visitsOf(string $step): int
    {
        return $this->visits[$step] ?? 0;
    }

    public function startingAt(?string $step): self
    {
        return $this->with(['next' => $step, 'status' => $step === null ? Status::Completed : Status::Running]);
    }

    public function entering(string $step): self
    {
        return $this->with([
            'status' => Status::Running,
            'visits' => [$step => $this->visitsOf($step) + 1] + $this->visits,
            'failure' => null,
            'pause' => null,
        ]);
    }

    /**
     * @throws WorkflowException When the output is not plain data
     */
    public function withOutput(string $step, mixed $output, ?string $next): self
    {
        $this->assertPlain($output, sprintf('The output of step "%s"', $step));
        $progress = $this->progress;
        unset($progress[$step]);

        return $this->with([
            'values' => [$step => $output] + $this->values,
            'last' => $step,
            'next' => $next,
            'progress' => $progress,
            'status' => $next === null ? Status::Completed : Status::Running,
        ]);
    }

    public function spending(string $step, Usage $spent): self
    {
        return $this->with(['usage' => [$step => $this->usage($step)->plus($spent)] + $this->usage]);
    }

    /**
     * @throws WorkflowException When the payload is not plain data
     */
    public function pausedAt(string $step, mixed $payload): self
    {
        $this->assertPlain($payload, sprintf('What step "%s" pauses with', $step));

        return $this->with(['status' => Status::Paused, 'next' => $step, 'pause' => $payload]);
    }

    public function failedAt(string $step, string $message): self
    {
        return $this->with(['status' => Status::Failed, 'next' => $step, 'failure' => $message]);
    }

    public function limitReachedAt(string $step): self
    {
        return $this->with(['status' => Status::LimitReached, 'next' => $step]);
    }

    public function isWorkingThrough(string $step): bool
    {
        return isset($this->progress[$step]);
    }

    /**
     * @param list<mixed> $items
     *
     * @throws WorkflowException When the items are not plain data
     */
    public function workingThrough(string $step, array $items): self
    {
        $this->assertPlain($items, sprintf('The items of step "%s"', $step));

        return $this->with(['progress' => [$step => ['items' => $items, 'results' => []]] + $this->progress]);
    }

    public function hasItemLeft(string $step): bool
    {
        return count($this->progress[$step]['results'] ?? []) < count($this->progress[$step]['items'] ?? []);
    }

    public function itemLeft(string $step): mixed
    {
        return $this->progress[$step]['items'][count($this->progress[$step]['results'])];
    }

    /**
     * @throws WorkflowException When the result is not plain data
     */
    public function withItemDone(string $step, mixed $result): self
    {
        $this->assertPlain($result, sprintf('A result of step "%s"', $step));
        $progress = $this->progress;
        $progress[$step]['results'][] = $result;

        return $this->with(['progress' => $progress]);
    }

    /**
     * @return list<mixed>
     */
    public function itemsDone(string $step): array
    {
        return $this->progress[$step]['results'] ?? [];
    }

    /**
     * @param array<string, mixed> $changes Constructor arguments to replace, by name
     */
    private function with(array $changes): self
    {
        return new self(...$changes + [
            'id' => $this->id,
            'workflow' => $this->workflow,
            'status' => $this->status,
            'values' => $this->values,
            'next' => $this->next,
            'last' => $this->last,
            'visits' => $this->visits,
            'usage' => $this->usage,
            'progress' => $this->progress,
            'pause' => $this->pause,
            'failure' => $this->failure,
        ]);
    }

    /**
     * State has to survive JSON, so it holds scalars, null and arrays of them only.
     *
     * @throws WorkflowException When the value holds an object, a closure or a resource
     */
    private function assertPlain(mixed $value, string $what): void
    {
        if (is_array($value)) {
            foreach ($value as $inner) {
                $this->assertPlain($inner, $what);
            }

            return;
        }

        if ($value !== null && !is_scalar($value)) {
            throw new WorkflowException(sprintf(
                '%s must be plain data (scalars and arrays), so the run can be stored; it holds a %s.',
                $what,
                get_debug_type($value),
            ));
        }
    }
}
