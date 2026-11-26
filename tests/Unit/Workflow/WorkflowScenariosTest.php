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

use PapiAI\Core\Agent;
use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Storage\FileWorkflowStore;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Usage;
use PapiAI\Core\Workflow\JumpTo;
use PapiAI\Core\Workflow\Pause;
use PapiAI\Core\Workflow\State;
use PapiAI\Core\Workflow\Status;
use PapiAI\Core\Workflow\Workflow;

/**
 * An agent whose provider gives the scripted replies in order.
 */
function scripted(Response|Throwable ...$replies): Agent
{
    return new Agent(new ScriptedProvider(true, ...$replies), 'm');
}

function reply(string $text, int $input = 10, int $output = 5): Response
{
    return new Response($text, usage: new Usage($input, $output));
}

describe('Workflow scenarios', function () {
    it('1. routes a request to the agent a classifier picks, with a fallback', function (string $topic, string $answer) {
        $classifier = scripted(reply($topic));
        $billing = scripted(reply('Billing here.'));
        $technical = scripted(reply('Technical here.'));
        $general = scripted(reply('General here.'));

        $support = (new Workflow('support'))
            ->step('topic', fn (State $s) => $classifier->run($s->get('request')))
            ->branch('answer', fn (State $s) => strtolower(trim($s->get('topic'))), [
                'billing' => fn (State $s) => $billing->run($s->get('request')),
                'technical' => fn (State $s) => $technical->run($s->get('request')),
            ], otherwise: fn (State $s) => $general->run($s->get('request')));

        $result = $support->start(['request' => 'I was charged twice.']);

        expect($result->status)->toBe(Status::Completed);
        expect($result->output)->toBe($answer);
        expect($result->usage->totalTokens)->toBe(30);
    })->with([
        'a known topic' => [" Billing\n", 'Billing here.'],
        'something unexpected' => ['I think this is about refunds?', 'General here.'],
    ]);

    it('2. runs steps in order, each reading what earlier steps produced', function () {
        $planner = scripted(reply('{"sections": ["Why", "How"]}'));
        $writer = scripted(reply('Because.'), reply('Like this.'));
        $sections = Schema::object(['sections' => Schema::array(Schema::string())]);

        $article = (new Workflow('article'))
            ->step('outline', fn (State $s) => $planner->run($s->get('topic'), ['outputSchema' => $sections]))
            ->each(
                'sections',
                fn (State $s) => $s->get('outline')['sections'],
                fn (string $title, State $s) => $writer->run("Write the section: {$title}"),
            )
            ->step('article', fn (State $s) => implode("\n\n", $s->get('sections')));

        $result = $article->start(['topic' => 'Workflows']);

        expect($result->output)->toBe("Because.\n\nLike this.");
        expect($result->state->get('outline'))->toBe(['sections' => ['Why', 'How']]);
        expect($result->usage->totalTokens)->toBe(45);
    });

    it('3. revises a draft until the critic approves', function () {
        $writer = scripted(reply('Draft one'), reply('Draft two'));
        $critic = scripted(reply('{"approved": false, "issues": ["Too vague"]}'), reply('{"approved": true, "issues": []}'));
        $verdict = Schema::object(['approved' => Schema::boolean(), 'issues' => Schema::array(Schema::string())]);

        $reviewed = (new Workflow('reviewed-draft'))
            ->step('draft', fn (State $s) => $writer->run($s->has('review')
                ? "Revise:\n{$s->get('draft')}\nIssues:\n" . implode("\n", $s->get('review')['issues'])
                : $s->get('brief')), atMost: 3)
            ->step('review', fn (State $s) => $critic->run($s->get('draft'), ['outputSchema' => $verdict]))
            ->step('decide', fn (State $s) => $s->get('review')['approved'] ? null : new JumpTo('draft'));

        $result = $reviewed->start(['brief' => 'Write about workflows.']);

        expect($result->status)->toBe(Status::Completed);
        expect($result->state->get('draft'))->toBe('Draft two');
    });

    it('3. gives up after the draft limit, with the last draft and the review of that draft', function () {
        $writer = scripted(reply('Draft one'), reply('Draft two'), reply('Draft three'));
        $critic = scripted(
            reply('{"approved": false, "issues": ["One is vague"]}'),
            reply('{"approved": false, "issues": ["Two is vague"]}'),
            reply('{"approved": false, "issues": ["Three is vague"]}'),
        );
        $verdict = Schema::object(['approved' => Schema::boolean(), 'issues' => Schema::array(Schema::string())]);

        $reviewed = (new Workflow('reviewed-draft'))
            ->step('draft', fn (State $s) => $writer->run($s->get('brief')), atMost: 3)
            ->step('review', fn (State $s) => $critic->run($s->get('draft'), ['outputSchema' => $verdict]))
            ->step('decide', fn (State $s) => $s->get('review')['approved'] ? null : new JumpTo('draft'));

        $result = $reviewed->start(['brief' => 'Write about workflows.']);

        expect($result->status)->toBe(Status::LimitReached);
        expect($result->state->get('draft'))->toBe('Draft three');
        expect($result->state->get('review')['issues'])->toBe(['Three is vague']);
        expect($result->error)->toBeNull();
    });

    it('4. pauses for a person and resumes in another process with their decision', function () {
        $directory = sys_get_temp_dir() . '/papi-workflow-' . uniqid();
        $published = [];
        $define = function (Agent $writer) use ($directory, &$published): Workflow {
            return (new Workflow('publish', new FileWorkflowStore($directory)))
                ->step('draft', fn (State $s) => $writer->run($s->get('brief')))
                ->step('approval', fn (State $s) => new Pause(['draft' => $s->get('draft')]))
                ->step('publish', function (State $s) use (&$published) {
                    if ($s->get('approval')['approved']) {
                        $published[] = $s->get('draft');
                    }

                    return $s->get('approval')['approved'];
                });
        };

        $paused = $define(scripted(reply('The draft.')))->start(['brief' => 'Announce the release.']);

        expect($paused->status)->toBe(Status::Paused);
        expect($paused->state->pausedFor())->toBe(['draft' => 'The draft.']);
        expect($published)->toBe([]);

        // Another process: a new Workflow object, and a writer that must not be asked again.
        $resumed = $define(scripted(new RuntimeException('The draft must not be written twice')))
            ->resume($paused->state->id, ['approved' => true]);

        expect($resumed->status)->toBe(Status::Completed);
        expect($published)->toBe(['The draft.']);
        expect($resumed->usage->totalTokens)->toBe(15);
    });

    it('4. carries a paused run as plain data through JSON', function () {
        $workflow = (new Workflow('publish'))
            ->step('approval', fn (State $s) => new Pause('Publish?'))
            ->step('publish', fn (State $s) => $s->get('approval') === 'yes' ? 'published' : 'held');

        $row = json_encode($workflow->start(['brief' => 'x'])->state->toArray(), JSON_THROW_ON_ERROR);
        $resumed = $workflow->resume(State::fromArray(json_decode($row, true, flags: JSON_THROW_ON_ERROR)), 'yes');

        expect($resumed->output)->toBe('published');
    });

    it('5. continues at the failed step on a retry, without repeating or re-paying for the finished ones', function () {
        $directory = sys_get_temp_dir() . '/papi-workflow-' . uniqid();
        $planner = scripted(reply('{"sections": ["One", "Two", "Three"]}'));
        $writer = scripted(reply('First.'), new RuntimeException('Provider outage'), reply('Second.'), reply('Third.'));
        $sections = Schema::object(['sections' => Schema::array(Schema::string())]);
        $define = fn (): Workflow => (new Workflow('article', new FileWorkflowStore($directory)))
            ->step('outline', fn (State $s) => $planner->run($s->get('topic'), ['outputSchema' => $sections]))
            ->each(
                'sections',
                fn (State $s) => $s->get('outline')['sections'],
                fn (string $title, State $s) => $writer->run("Write: {$title}"),
            );

        $failed = $define()->start(['topic' => 'Workflows'], 'job-1');

        expect($failed->status)->toBe(Status::Failed);
        expect($failed->error?->getMessage())->toBe('Provider outage');
        expect($failed->state->failure())->toBe('Provider outage');

        $retried = $define()->resume('job-1');

        expect($retried->status)->toBe(Status::Completed);
        expect($retried->output)->toBe(['First.', 'Second.', 'Third.']);
        expect($planner->getProvider()->calls)->toHaveCount(1);
        expect($writer->getProvider()->calls)->toHaveCount(4);
        expect($retried->usage->totalTokens)->toBe(60);
    });
});
