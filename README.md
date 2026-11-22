# PapiAI Core

[![CI](https://github.com/papi-ai/papi-core/workflows/CI/badge.svg)](https://github.com/papi-ai/papi-core/actions?query=workflow%3ACI) [![Latest Version](https://img.shields.io/packagist/v/papi-ai/papi-core.svg)](https://packagist.org/packages/papi-ai/papi-core) [![Total Downloads](https://img.shields.io/packagist/dt/papi-ai/papi-core.svg)](https://packagist.org/packages/papi-ai/papi-core) [![PHP Version](https://img.shields.io/packagist/php-v/papi-ai/papi-core.svg)](https://packagist.org/packages/papi-ai/papi-core) [![License](https://img.shields.io/packagist/l/papi-ai/papi-core.svg)](https://packagist.org/packages/papi-ai/papi-core)

A simple but powerful PHP library for building AI agents. Framework-agnostic, type-safe, and designed for real-world applications.

## Features

- **Framework-agnostic** - Works standalone, with Laravel, Symfony, or any PHP project
- **Multi-provider** - Supports Anthropic Claude, Google Gemini, and more
- **Tool calling** - Define tools as functions or class methods with attributes
- **Structured output** - Zod-like schema validation for LLM responses
- **Streaming** - First-class streaming support with events
- **Hooks** - Observability hooks for logging, metrics, and error handling
- **Workflows** - Multi-agent steps over plain data that pause, resume and recover, plus agent handoffs
- **Type-safe** - Full PHP 8.2+ type hints and strict types

## Installation

```bash
composer require papi-ai/core
```

For providers, install the ones you need:

```bash
composer require papi-ai/anthropic  # For Claude
composer require papi-ai/google     # For Gemini
```

## Quick Start

```php
use PapiAI\Core\Agent;
use PapiAI\Anthropic\AnthropicProvider;

$agent = new Agent(
    provider: new AnthropicProvider(apiKey: $_ENV['ANTHROPIC_API_KEY']),
    model: 'claude-sonnet-5-5',
    instructions: 'You are a helpful assistant.',
);

$response = $agent->run('What is 2 + 2?');
echo $response->text; // "4"
```

## Adding Tools

Tools let the agent perform actions and retrieve information.

### Function-based Tools

```php
use PapiAI\Core\Tool;

$weatherTool = Tool::make(
    name: 'get_weather',
    description: 'Get current weather for a city',
    parameters: [
        'city' => ['type' => 'string', 'description' => 'City name'],
    ],
    handler: fn(array $args) => fetchWeather($args['city']),
);

$agent = new Agent(
    provider: $provider,
    model: 'claude-sonnet-5-5',
    tools: [$weatherTool],
);

$response = $agent->run('What is the weather in London?');
```

### Class-based Tools with Attributes

```php
use PapiAI\Core\Tool;
use PapiAI\Core\Attributes\Tool as ToolAttr;
use PapiAI\Core\Attributes\Description;

class WebTools
{
    #[ToolAttr('Fetch content from a URL')]
    public function fetchUrl(
        #[Description('The URL to fetch')] string $url,
        #[Description('Timeout in seconds')] int $timeout = 30,
    ): string {
        return file_get_contents($url);
    }

    #[ToolAttr('Search the web')]
    public function search(string $query, int $limit = 10): array
    {
        // Implementation
    }
}

$agent = new Agent(
    provider: $provider,
    model: 'claude-sonnet-5-5',
    tools: Tool::fromClass(WebTools::class),
);
```

## Structured Output

Use schemas to validate and parse LLM responses:

```php
use PapiAI\Core\Schema\Schema;

$schema = Schema::object([
    'sentiment' => Schema::enum(['positive', 'negative', 'neutral']),
    'confidence' => Schema::number()->min(0)->max(1),
    'keywords' => Schema::array(Schema::string()),
]);

$response = $agent->run(
    prompt: 'Analyze: "Great product, highly recommend!"',
    options: ['outputSchema' => $schema],
);

// $response->data is validated
$response->data['sentiment'];   // 'positive'
$response->data['confidence'];  // 0.95
$response->data['keywords'];    // ['great', 'recommend']
```

The schema is enforced by the provider when it can enforce one. For a provider that cannot, the
agent states the schema in the system instructions itself, so there is no need to repeat it in
yours. Either way the reply is validated against the full schema; if it does not fit, the agent
sends the errors back once and asks for a corrected object before giving up with an
`InvalidArgumentException`.

### Schema Types

```php
Schema::string()              // String values
Schema::string()->min(1)      // Min length
Schema::string()->max(100)    // Max length
Schema::string()->pattern('/regex/')

Schema::number()              // Float values
Schema::integer()             // Integer values
Schema::number()->min(0)      // Minimum value
Schema::number()->max(100)    // Maximum value

Schema::boolean()             // Boolean values

Schema::array(Schema::string())      // Array of strings
Schema::array($itemSchema)->minItems(1)->maxItems(10)

Schema::object([              // Object with properties
    'name' => Schema::string(),
    'age' => Schema::integer()->optional(),
])

Schema::enum(['a', 'b', 'c']) // Enum values

// Modifiers
->nullable()                  // Allow null
->optional()                  // Not required in objects
->default('value')            // Default value
->description('...')          // Description for LLM
```

## Streaming

### Simple Text Streaming

```php
foreach ($agent->stream('Tell me a story') as $chunk) {
    echo $chunk->text;
    flush();
}
```

### Event Streaming

```php
foreach ($agent->streamEvents('Use tools to help me') as $event) {
    match ($event->type) {
        'text' => echo $event->text,
        'tool_call' => echo "Calling: {$event->tool}\n",
        'tool_result' => echo "Result: " . json_encode($event->result) . "\n",
        'done' => echo "\nComplete!\n",
        'error' => echo "Error: {$event->error}\n",
    };
}
```

## Hooks

Add observability to your agent:

```php
$agent = new Agent(
    provider: $provider,
    model: 'claude-sonnet-5-5',
    tools: $tools,
    hooks: [
        'beforeToolCall' => function (string $name, array $input) {
            Log::info("Calling tool: {$name}", $input);
        },
        'afterToolCall' => function (string $name, mixed $result, float $duration) {
            Metrics::timing("tool.{$name}", $duration);
        },
        'onError' => function (Throwable $error) {
            Sentry::captureException($error);
        },
    ],
);
```

## Messages & Conversation

Build conversations manually:

```php
use PapiAI\Core\Message;
use PapiAI\Core\Conversation;

// Individual messages
$message = Message::user('Hello');
$message = Message::system('You are helpful');
$message = Message::assistant('Hi there!');
$message = Message::userWithImage('What is this?', $imageUrl);

// Conversation helper
$conversation = new Conversation();
$conversation->setSystem('You are a helpful assistant');
$conversation->addUser('Hello');
$conversation->addAssistant('Hi! How can I help?');
$conversation->addUser('Tell me a joke');

$messages = $conversation->getMessages();
```

If you keep the transcript yourself, continue it with `$response->toMessage()` rather than
`Message::assistant($response->text, ...)`. Some providers leave state on the response that they
need back on the next request, such as reasoning signatures, and `toMessage()` carries it. It is
plain data under `$message->replay`, keyed by provider name, so `toArray()` and `fromArray()`
store and restore it with the rest of the message.

To have an agent answer a stored conversation, pass it to `continue()`:

```php
$conversation->addUser('And in Celsius?');
$response = $agent->continue($conversation);
```

The agent's own instructions take the place of the conversation's. On success, everything the
run added (tool calls, their results and the answer) is appended to the conversation, so saving
it afterwards is enough. If the run throws, the conversation is left as it was.

## Token Usage

```php
$response = $agent->run('What is the weather in Lisbon?');

$response->usage->inputTokens;           // every provider call in the run, tool turns included
$response->usage->outputTokens;
$response->lastCallUsage->inputTokens;   // the final call alone: how full the context is
```

`Usage::plus()` adds two usages. Tokens spent by a run that ends in an exception are not
reported, nor are those of attempts that `RetryMiddleware` retried.

## Workflows

Several agents, and ordinary PHP, as named steps over plain data that can pause, resume and
recover from a failure:

```php
use PapiAI\Core\Workflow\JumpTo;
use PapiAI\Core\Workflow\State;
use PapiAI\Core\Workflow\Workflow;

$reviewed = (new Workflow('reviewed-draft'))
    ->step('draft', fn (State $s) => $writer->run($s->get('brief')))
    ->step('review', fn (State $s) => $critic->run($s->get('draft'), ['outputSchema' => $verdict]), atMost: 3)
    ->step('decide', fn (State $s) => $s->get('review')['approved'] ? null : new JumpTo('draft'));

$result = $reviewed->start(['brief' => 'Announce version 2 of our API.']);
```

See [docs/workflows.md](docs/workflows.md) for routing, lists, human approval, resuming after a
failure, and `Handoffs` for agents that pass a chat to one another.

## Why a Response Stopped

Every provider reports why the model stopped in the same words:

```php
use PapiAI\Core\StopReason;

$response = $agent->run('Summarise this repository');

if ($response->isTruncated()) {
    // The output ceiling cut the answer short: raise maxTokens and ask again.
}

match ($response->stoppedBecause) {
    StopReason::Stop => 'finished',
    StopReason::ToolCalls => 'wants tools run',
    StopReason::MaxTokens => 'cut short',
    StopReason::Refused => 'declined or filtered',
    StopReason::Other => 'something else',
};
```

The provider's own string is still on `$response->stopReason`.

## Configuration Options

```php
$agent = new Agent(
    provider: $provider,
    model: 'claude-sonnet-5-5',
    instructions: 'System prompt here',
    tools: [...],
    hooks: [...],
    maxTokens: 4096,        // Max response tokens
    temperature: 0.7,       // Only on models that still take it; current Claude and Gemini refuse it
    maxTurns: 10,           // Max tool-use loops before stopping
);
```

Per run, `$agent->run($prompt, ['effort' => Effort::High])` sets the reasoning effort, as the
`Effort` enum or its string value.

## Providers

### Anthropic (Claude)

```php
use PapiAI\Anthropic\AnthropicProvider;

$provider = new AnthropicProvider(
    apiKey: $_ENV['ANTHROPIC_API_KEY'],
    defaultModel: 'claude-sonnet-5-5',
);
```

### Google (Gemini)

```php
use PapiAI\Google\GoogleProvider;

$provider = new GoogleProvider(
    apiKey: $_ENV['GOOGLE_API_KEY'],
    defaultModel: GoogleProvider::MODEL_3_8_FLASH,
);

// Some of the available models
GoogleProvider::MODEL_4_HYDROGEN // gemini-4-hydrogen
GoogleProvider::MODEL_4_CARBON   // gemini-4-carbon
GoogleProvider::MODEL_3_8_FLASH  // gemini-3.8-flash (default)
GoogleProvider::MODEL_3_1_PRO    // gemini-3.1-pro-preview
GoogleProvider::MODEL_2_5_PRO    // gemini-2.5-pro
```

### Choosing a Provider by Name

An application that lets its users pick a provider in configuration does not need to know any
provider's class or constructor. Every provider package ships a factory, and `ComposerDiscovery`
finds the ones that are installed:

```php
use PapiAI\Core\Factory\ComposerDiscovery;
use PapiAI\Core\Factory\ProviderRegistry;

$registry = new ProviderRegistry(new ComposerDiscovery());

$provider = $registry->create('google', ['api_key' => $_ENV['GOOGLE_API_KEY']]);

foreach ($registry->installed() as $factory) {
    echo $factory->package(), ': ', $factory->name(), "\n";

    foreach ($factory->settings() as $setting) {
        echo '  ', $setting->name, $setting->required ? ' (required)' : '', ': ', $setting->description, "\n";
    }
}
```

Setting names are snake_case (`api_key`, `base_url`, `model`, `max_tokens`, `effort`). A missing,
unknown or mistyped setting throws `ConfigurationException`, an `InvalidArgumentException`, before
any request is made. The registry accepts any list of factories, so tests can hand it their own.

## Creating Custom Providers

Implement `ProviderInterface`:

```php
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Response;

class MyProvider implements ProviderInterface
{
    public function chat(array $messages, array $options = []): Response
    {
        // Make API call and return Response
    }

    public function stream(array $messages, array $options = []): iterable
    {
        // Yield StreamChunk objects
    }

    public function supportsTool(): bool { return true; }
    public function supportsVision(): bool { return true; }
    public function supportsStructuredOutput(): bool { return false; }
    public function getName(): string { return 'my-provider'; }
}
```

If your API needs opaque state back on the next request, return it as `replay: ['my-provider' => $state]`
on the `Response`, and read it from `$message->replayFor('my-provider')` when you convert the history.

To make it discoverable by name, ship a factory with it. Extend `AbstractProviderFactory`,
declare your settings, and build the provider from them:

```php
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Factory\AbstractProviderFactory;
use PapiAI\Core\Factory\Setting;
use PapiAI\Core\Factory\SettingValues;

final class MyProviderFactory extends AbstractProviderFactory
{
    public function name(): string { return 'my-provider'; }
    public function package(): string { return 'acme/my-provider'; }

    public function settings(): array
    {
        return [new Setting('api_key', true, 'My API key')];
    }

    protected function build(SettingValues $values): ProviderInterface
    {
        return new MyProvider($values->string('api_key'));
    }
}
```

Then declare the package type and the factory in its `composer.json`:

```json
{
    "type": "papi-provider",
    "extra": {"papi": {"factory": "Acme\\MyProviderFactory"}}
}
```

## Upgrading to 0.15.7

- `$response->usage` on an agent response is now the total of the whole run. It used to be the
  final provider call only, which under-reported any run that called tools. That figure is now
  `$response->lastCallUsage`.
- With `outputSchema`, a reply that does not fit is corrected once before the agent throws, and
  providers without native support are told the schema in the system instructions.

## Testing

```bash
composer test
```

## License

MIT
