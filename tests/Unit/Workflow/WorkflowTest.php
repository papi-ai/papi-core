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

use PapiAI\Core\Storage\FileWorkflowStore;
use PapiAI\Core\Storage\InMemoryWorkflowStore;
use PapiAI\Core\Workflow\Finish;
use PapiAI\Core\Workflow\JumpTo;
use PapiAI\Core\Workflow\Pause;
use PapiAI\Core\Workflow\State;
use PapiAI\Core\Workflow\Status;
use PapiAI\Core\Workflow\Workflow;
use PapiAI\Core\Workflow\WorkflowException;

describe('Workflow', function () {
    describe('steps', function () {
        it('runs a step that makes no model call', function () {
            $result = (new Workflow('shout'))->step('loud', fn (State $s) => strtoupper($s->get('text')))->start(['text' => 'hi']);

            expect($result->output)->toBe('HI');
            expect($result->usage->totalTokens)->toBe(0);
        });

        it('ends early when a step finishes the run', function () {
            $result = (new Workflow('early'))
                ->step('check', fn (State $s) => new Finish('nothing to do'))
                ->step('work', fn (State $s) => 'worked')
                ->start([]);

            expect($result->status)->toBe(Status::Completed);
            expect($result->output)->toBe('nothing to do');
            expect($result->state->has('work'))->toBeFalse();
        });

        it('stores what a jumping step produced', function () {
            $result = (new Workflow('skip'))
                ->step('first', fn (State $s) => new JumpTo('third', 'skipping'))
                ->step('second', fn (State $s) => 'never')
                ->step('third', fn (State $s) => $s->get('first') . ' worked')
                ->start([]);

            expect($result->output)->toBe('skipping worked');
        });

        it('gives every run an ID of its own, or the one asked for', function () {
            $workflow = (new Workflow('ids'))->step('only', fn (State $s) => 1);

            expect($workflow->start([])->state->id)->not->toBe($workflow->start([])->state->id);
            expect($workflow->start([], 'job-7')->state->id)->toBe('job-7');
        });

        it('stops a step that would run more often than the default limit', function () {
            $result = (new Workflow('forever'))->step('again', fn (State $s) => new JumpTo('again'))->start([]);

            expect($result->status)->toBe(Status::LimitReached);
            expect($result->state->visitsOf('again'))->toBe(Workflow::DEFAULT_LIMIT);
        });

        it('works through an empty list', function () {
            $result = (new Workflow('none'))->each('items', fn (State $s) => [], fn (mixed $item, State $s) => $item)->start([]);

            expect($result->output)->toBe([]);
        });

        it('works through more items than the default limit', function () {
            $result = (new Workflow('many'))
                ->each('doubled', fn (State $s) => range(1, 40), fn (int $n, State $s) => $n * 2)
                ->start([]);

            expect($result->status)->toBe(Status::Completed);
            expect($result->output)->toHaveCount(40);
        });

        it('asks for the list once, however often the run is resumed', function () {
            $asked = 0;
            $fail = true;
            $workflow = (new Workflow('once'))->each('items', function (State $s) use (&$asked) {
                ++$asked;

                return ['a', 'b'];
            }, function (string $item, State $s) use (&$fail) {
                if ($item === 'b' && $fail) {
                    $fail = false;

                    throw new RuntimeException('b failed');
                }

                return $item;
            });

            $result = $workflow->resume($workflow->start([])->state);

            expect($result->output)->toBe(['a', 'b']);
            expect($asked)->toBe(1);
        });
    });

    describe('progress', function () {
        it('tells the application as steps start, finish, pause and fail', function () {
            $seen = [];
            $workflow = (new Workflow('watched'))
                ->step('one', fn (State $s) => 1)
                ->step('ask', fn (State $s) => new Pause('Go on?'))
                ->step('boom', fn (State $s) => throw new RuntimeException('Out of luck'))
                ->hook('beforeStep', function (string $step, State $state) use (&$seen) {
                    $seen[] = "start {$step}";
                })
                ->hook('afterStep', function (string $step, State $state) use (&$seen) {
                    $seen[] = "done {$step} = " . json_encode($state->get($step));
                })
                ->hook('onPause', function (string $step, mixed $payload, State $state) use (&$seen) {
                    $seen[] = "pause {$step}: {$payload}";
                })
                ->hook('onStepFailed', function (string $step, Throwable $error, State $state) use (&$seen) {
                    $seen[] = "fail {$step}: {$error->getMessage()}";
                });

            $workflow->resume($workflow->start([])->state, 'yes');

            expect($seen)->toBe(['start one', 'done one = 1', 'start ask', 'pause ask: Go on?', 'start boom', 'fail boom: Out of luck']);
        });

        it('refuses a hook it does not have', function () {
            (new Workflow('watched'))->hook('onFinish', fn () => null);
        })->throws(WorkflowException::class, 'Workflows have no "onFinish" hook. They have: beforeStep, afterStep, onStepFailed, onPause.');
    });

    describe('resume', function () {
        beforeEach(function () {
            $this->published = 0;
            $this->store = new InMemoryWorkflowStore();
            $this->workflow = (new Workflow('publish', $this->store))
                ->step('approval', fn (State $s) => new Pause('Publish?'))
                ->step('publish', function (State $s) {
                    ++$this->published;

                    return 'published';
                });
        });

        it('refuses a run that already completed, so a double click publishes once', function () {
            $id = $this->workflow->start([])->state->id;
            $this->workflow->resume($id, 'yes');

            expect(fn () => $this->workflow->resume($id, 'yes'))
                ->toThrow(WorkflowException::class, sprintf('Run "%s" is completed, so there is nothing to resume.', $id));
            expect($this->published)->toBe(1);
        });

        it('refuses a run another worker is on, unless told that worker died', function () {
            $running = (new State('job-1', 'publish'))->startingAt('publish');
            $this->store->save($running);

            expect(fn () => $this->workflow->resume('job-1'))
                ->toThrow(WorkflowException::class, 'Run "job-1" is running, so there is nothing to resume.');
            expect($this->workflow->resume('job-1', evenIfRunning: true)->status)->toBe(Status::Completed);
        });

        it('refuses a run of another workflow', function () {
            $other = (new Workflow('archive'))->step('wait', fn (State $s) => new Pause())->start([], 'run-9');

            expect(fn () => $this->workflow->resume($other->state))
                ->toThrow(WorkflowException::class, 'Run "run-9" belongs to workflow "archive", not "publish".');
        });

        it('refuses a run waiting at a step that was renamed or removed', function () {
            $before = (new Workflow('publish'))->step('sign_off', fn (State $s) => new Pause())->start([], 'run-3');

            expect(fn () => $this->workflow->resume($before->state))->toThrow(
                WorkflowException::class,
                'Run "run-3" is to continue at step "sign_off", which workflow "publish" does not have. It has: "approval", "publish".',
            );
        });

        it('refuses an ID the store does not hold, and an ID when there is no store', function () {
            expect(fn () => $this->workflow->resume('missing'))->toThrow(WorkflowException::class, 'No run "missing" is stored.');
            expect(fn () => (new Workflow('publish'))->step('a', fn () => 1)->resume('job-1'))
                ->toThrow(WorkflowException::class, 'Workflow "publish" has no store to load run "job-1" from. Pass the State instead.');
        });

        it('refuses to start a second run under a stored ID', function () {
            $this->workflow->start([], 'job-1');

            expect(fn () => $this->workflow->start([], 'job-1'))
                ->toThrow(WorkflowException::class, 'A run "job-1" is already stored. Resume it, or start under another ID.');
        });
    });

    describe('misuse', function () {
        it('refuses a step output that could not be stored', function () {
            (new Workflow('objects'))->step('make', fn (State $s) => new DateTimeImmutable())->start([]);
        })->throws(WorkflowException::class, 'The output of step "make" must be plain data (scalars and arrays), so the run can be stored; it holds a DateTimeImmutable.');

        it('refuses input that could not be stored', function () {
            (new Workflow('objects'))->step('make', fn (State $s) => 1)->start(['callback' => fn () => 1]);
        })->throws(WorkflowException::class, 'A workflow input must be plain data');

        it('refuses a list item that pauses', function () {
            (new Workflow('items'))->each('items', fn (State $s) => [1], fn (int $n, State $s) => new Pause())->start([]);
        })->throws(WorkflowException::class, 'Step "items" works through a list, so an item cannot return PapiAI\Core\Workflow\Pause. Do that in a step of its own.');

        it('refuses a list that is not one', function () {
            (new Workflow('items'))->each('items', fn (State $s) => 'a, b', fn (mixed $item, State $s) => $item)->start([]);
        })->throws(WorkflowException::class, 'Step "items" needs a list to work through, but got string.');

        it('refuses two steps of one name, a jump to nowhere and a workflow without steps', function () {
            expect(fn () => (new Workflow('twice'))->step('a', fn () => 1)->step('a', fn () => 2))
                ->toThrow(WorkflowException::class, 'Workflow "twice" needs a unique, non-empty name for each step; "a" will not do.');
            expect(fn () => (new Workflow('lost'))->step('a', fn () => new JumpTo('nowhere'))->start([], 'r1'))
                ->toThrow(WorkflowException::class, 'Run "r1" is to continue at step "nowhere", which workflow "lost" does not have. It has: "a".');
            expect(fn () => (new Workflow('empty'))->start([]))->toThrow(WorkflowException::class, 'Workflow "empty" has no steps.');
        });
    });

    describe('stores', function () {
        it('keeps a run in a file and gives it back equal', function () {
            $store = new FileWorkflowStore(sys_get_temp_dir() . '/papi-workflow-' . uniqid() . '/nested');
            $state = (new Workflow('publish'))->step('wait', fn (State $s) => new Pause(['draft' => 'x']))->start(['n' => 1], 'jobs/42')->state;

            $store->save($state);

            expect($store->load('jobs/42')?->toArray())->toBe($state->toArray());
            expect($store->load('jobs_42'))->toBeNull();

            $store->delete('jobs/42');

            expect($store->load('jobs/42'))->toBeNull();
        });

        it('says so when a stored run is not one', function () {
            State::fromArray(['id' => 'x']);
        })->throws(WorkflowException::class, 'This is not a stored workflow run');
    });
});
