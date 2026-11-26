# Workflows

A workflow is a named list of steps that run in order over a `State` of plain data. A step is
ordinary PHP: it can run an agent, call a provider, or make no model call at all. Because the
state is plain data, a run can pause for hours, move to another process, or pick up after a
failure without repeating the steps that finished.

Every example below shares this setup:

```php
use PapiAI\Anthropic\AnthropicProvider;
use PapiAI\Core\Agent;
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Workflow\JumpTo;
use PapiAI\Core\Workflow\Pause;
use PapiAI\Core\Workflow\State;
use PapiAI\Core\Workflow\Status;
use PapiAI\Core\Workflow\Workflow;

$provider = new AnthropicProvider(apiKey: $_ENV['ANTHROPIC_API_KEY']);
$agent = fn (string $instructions, string $model = 'claude-sonnet-5-5'): Agent
    => new Agent($provider, $model, instructions: $instructions);
```

## Steps

```php
$workflow = (new Workflow('greeting'))
    ->step('draft', fn (State $state) => $writer->run('Greet ' . $state->get('name')))
    ->step('shout', fn (State $state) => strtoupper($state->get('draft')));

$result = $workflow->start(['name' => 'Ada']);

$result->status;   // Status::Completed
$result->output;   // what the last step produced
$result->usage;    // tokens of every model call in every step
```

A step reads the input and the output of earlier steps with `$state->get('name')`; `has()` says
whether a step has run. What a step returns decides what happens next:

| A step returns | What happens |
|---|---|
| a `Response` | its `data` (or its `text`) becomes the step's output, and its usage is counted |
| any plain value | becomes the step's output |
| `new JumpTo('step', $output)` | the run continues at the named step |
| `new Pause($payload)` | the run stops until `resume()` is called with an answer |
| `new Finish($output)` | the run ends as completed |
| it throws | the run ends as `Status::Failed`; `resume()` runs the step again |

Outputs must be plain data (scalars and arrays), so the run can be stored. Returning an object
other than a `Response` throws a `WorkflowException`.

A step that makes several provider calls itself adds their usage with `Usage::plus()` and
returns a `Response` carrying the total; steps that make no model call cost nothing.

## 1. Router

A cheap classifier picks the agent that answers. Anything unexpected goes to `otherwise`.

```php
$classifier = $agent('Classify the request. Reply with one word: billing, technical or other.', 'claude-haiku-5-5');
$billing = $agent('You answer billing questions.');
$technical = $agent('You answer technical questions.');
$general = $agent('You answer general questions.');

$support = (new Workflow('support'))
    ->step('topic', fn (State $s) => $classifier->run($s->get('request')))
    ->branch('answer', fn (State $s) => strtolower(trim($s->get('topic'))), [
        'billing' => fn (State $s) => $billing->run($s->get('request')),
        'technical' => fn (State $s) => $technical->run($s->get('request')),
    ], otherwise: fn (State $s) => $general->run($s->get('request')));

echo $support->start(['request' => 'I was charged twice this month.'])->output;
```

## 2. Sequence with state

Outline, write each section, assemble. `each()` works through a list one item at a time and
saves the run after every item, so its bound is the length of the list.

```php
$planner = $agent('Plan articles as a list of section titles.');
$writer = $agent('You write one section of an article.');
$outline = Schema::object(['sections' => Schema::array(Schema::string())]);

$article = (new Workflow('article'))
    ->step('outline', fn (State $s) => $planner->run($s->get('topic'), ['outputSchema' => $outline]))
    ->each(
        'sections',
        fn (State $s) => $s->get('outline')['sections'],
        fn (string $title, State $s) => $writer->run("Topic: {$s->get('topic')}\nWrite the section: {$title}"),
    )
    ->step('article', fn (State $s) => implode("\n\n", $s->get('sections')));

echo $article->start(['topic' => 'Workflows in PHP'])->output;
```

## 3. Writer and critic

A step may run at most `atMost` times in a run (25 by default). Reaching the limit is an
outcome, not an exception: the run ends as `Status::LimitReached` with everything done so far
still in the state. Put the limit on the step that produces the work, here `draft`, so the run
ends with the last draft and the review of that draft.

```php
$writer = $agent('You write short product announcements.');
$critic = $agent('You review announcements for accuracy and tone.');
$verdict = Schema::object(['approved' => Schema::boolean(), 'issues' => Schema::array(Schema::string())]);

$reviewed = (new Workflow('reviewed-draft'))
    ->step('draft', fn (State $s) => $writer->run($s->has('review')
        ? "Revise this draft:\n{$s->get('draft')}\n\nFix these issues:\n" . implode("\n", $s->get('review')['issues'])
        : $s->get('brief')), atMost: 3)
    ->step('review', fn (State $s) => $critic->run($s->get('draft'), ['outputSchema' => $verdict]))
    ->step('decide', fn (State $s) => $s->get('review')['approved'] ? null : new JumpTo('draft'));

$result = $reviewed->start(['brief' => 'Announce version 2 of our API.']);

if ($result->status === Status::LimitReached) {
    echo "Not approved after three drafts:\n" . implode("\n", $result->state->get('review')['issues']);
} else {
    echo $result->state->get('draft');
}
```

## 4. Human in the loop

Give the workflow a store and it saves the run after every step. A step that returns `Pause`
stops the run; the answer passed to `resume()` becomes that step's output.

```php
use PapiAI\Core\Storage\FileWorkflowStore;

$publish = (new Workflow('publish', new FileWorkflowStore(__DIR__ . '/var/workflows')))
    ->step('draft', fn (State $s) => $writer->run($s->get('brief')))
    ->step('approval', fn (State $s) => new Pause(['draft' => $s->get('draft')]))
    ->step('publish', fn (State $s) => $s->get('approval')['approved'] ? $cms->publish($s->get('draft')) : null);

// Request 1: start, and keep the ID with whatever the person will act on.
$result = $publish->start(['brief' => 'Announce version 2 of our API.']);
$review->create(runId: $result->state->id, draft: $result->state->pausedFor()['draft']);

// Hours later, in another process: the same workflow definition, resumed with the decision.
$result = $publish->resume($runId, ['approved' => true]);
```

Without a store, keep `$result->state->toArray()` wherever you like (a database row, a queue
message) and pass `State::fromArray($row)` to `resume()`.

## 5. Resume after failure

A step that throws ends the run as failed, with the run saved at that step. Run it again from a
worker retry and it continues there: finished steps, and finished items of an `each()` step, are
neither run nor paid for again.

```php
$store = new FileWorkflowStore(__DIR__ . '/var/workflows');
$workflow = (new Workflow('article', $store))
    ->step('outline', fn (State $s) => $planner->run($s->get('topic'), ['outputSchema' => $outline]))
    ->each('sections', fn (State $s) => $s->get('outline')['sections'], fn (string $title, State $s) => $writer->run($title));

// In the queue worker. The job ID is the run ID, so a retry finds the run.
$result = $store->load($jobId) !== null
    ? $workflow->resume($jobId)
    : $workflow->start(['topic' => $topic], $jobId);

if ($result->status === Status::Failed) {
    throw $result->error;   // the queue retries the job
}
```

`$result->usage` is the total across every attempt, because each step's usage is kept in the
state.

## Resuming safely

- `resume()` accepts a run that is paused or failed. A completed run, or one another worker is
  running, throws a `WorkflowException` and runs nothing, so a double click cannot publish twice.
- A run left `running` by a process that died is resumed with `resume($id, evenIfRunning: true)`.
- The check is read-then-write, so two workers that resume the same run at the same instant can
  both pass it. Where that can happen, guard `resume()` with your own lock, or use a store that
  locks the row.
- The run records its workflow's name and the step it waits at. Resuming it under another
  workflow, or after that step was renamed or removed, throws a `WorkflowException` that names
  the missing step.
- A step that crashes after its side effect but before the run is saved will run again. **Make
  steps with side effects idempotent**: publish under a key, or check before you send.

## Watching progress

```php
$workflow
    ->hook('beforeStep', fn (string $step, State $state) => $status->started($state->id, $step))
    ->hook('afterStep', fn (string $step, State $state) => $status->finished($state->id, $step))
    ->hook('onStepFailed', fn (string $step, Throwable $error, State $state) => $status->failed($state->id, $step, $error))
    ->hook('onPause', fn (string $step, mixed $payload, State $state) => $status->waiting($state->id, $step));
```

For an `each()` step, `beforeStep` and `afterStep` fire once when the list is read and once per item.

A step that pauses fires `beforeStep` and `onPause`. Its `afterStep` fires when `resume()` gives
it its answer, with the answer already in the state and saved; `beforeStep` is not repeated.

## Stores

`WorkflowStoreInterface` has three methods: `save(State $state)`, `load(string $id): ?State` and
`delete(string $id)`. Core ships `InMemoryWorkflowStore` and `FileWorkflowStore` (one JSON file per
run, replaced in one move). A database store is a few lines around `State::toArray()` and
`State::fromArray()`.

## 6. Handoff

`Handoffs` is for chats, where an agent decides mid-conversation that another should take over.
It works on a `Conversation`, with or without a workflow.

```php
use PapiAI\Core\Conversation;
use PapiAI\Core\Handoffs;
use PapiAI\Core\Storage\FileConversationStore;

$desk = new Handoffs([
    'triage' => $agent('You greet customers and pass them to the right colleague.', 'claude-haiku-5-5'),
    'billing' => $agent('You resolve billing problems.'),
    'technical' => $agent('You resolve technical problems.'),
], start: 'triage', descriptions: [
    'billing' => 'Charges, invoices and refunds',
    'technical' => 'Errors, outages and integration questions',
]);
$store = new FileConversationStore(__DIR__ . '/var/chats');

// First message
$conversation = $store->load($chatId) ?? new Conversation();
$conversation->addUser('I was charged twice this month.');
echo $desk->reply($conversation)->text;   // triage hands over; billing answers
$store->save($chatId, $conversation);

// Next message, in another request
$conversation = $store->load($chatId);
$conversation->addUser('It was invoice 1042.');
echo $desk->reply($conversation)->text;   // goes straight to billing
$store->save($chatId, $conversation);

echo $desk->current($conversation);       // "billing"
```

- Every agent gets a `transfer_to_<name>` tool for each of the others. Calling it ends that
  agent's turn at once, with no further model call.
- Which agent is current is read from the conversation: the target of its latest transfer, or
  the starting agent. Saving the conversation is all the bookkeeping there is.
- `reply()` appends what the agents added. If an agent throws, the conversation is left exactly
  as it was, so save it only after `reply()` returns.
- An agent sees the user's messages, what the assistants said, and a note for each handoff, such
  as `[triage handed this conversation over to you.]`. **Tool calls and their results are not
  shown**, not even an agent's own from an earlier turn, and neither is provider replay data such
  as reasoning signatures: both belong to the run that produced them. An agent that needs what a
  tool found on a later turn should say it in its reply. The stored conversation keeps everything.
- One `reply()` follows at most five handoffs (`maxHops`). If the agents hand over once more
  without answering, `reply()` throws a `HandoffLimitException` and leaves the conversation as
  it was; the exception carries the tokens spent.

A single agent can also continue a stored conversation directly, with `$agent->continue($conversation)`.
