# PapiAI Core

[![Tests](https://github.com/papi-ai/core/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/papi-ai/core/actions/workflows/tests.yml)

A simple but powerful PHP library for building AI agents. Framework-agnostic, type-safe, and designed for real-world applications.

## Features

- **Framework-agnostic** - Works standalone, with Laravel, Symfony, or any PHP project
- **Multi-provider** - Supports Anthropic Claude, Google Gemini, and more
- **Tool calling** - Define tools as functions or class methods with attributes
- **Structured output** - Zod-like schema validation for LLM responses
- **Streaming** - First-class streaming support with events
- **Hooks** - Observability hooks for logging, metrics, and error handling
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
    model: 'claude-sonnet-4-20250514',
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
    model: 'claude-sonnet-4-20250514',
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
    model: 'claude-sonnet-4-20250514',
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
    model: 'claude-sonnet-4-20250514',
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

## Configuration Options

```php
$agent = new Agent(
    provider: $provider,
    model: 'claude-sonnet-4-20250514',
    instructions: 'System prompt here',
    tools: [...],
    hooks: [...],
    maxTokens: 4096,        // Max response tokens
    temperature: 0.7,       // 0.0 = deterministic, 1.0 = creative
    maxTurns: 10,           // Max tool-use loops before stopping
);
```

## Providers

### Anthropic (Claude)

```php
use PapiAI\Anthropic\AnthropicProvider;

$provider = new AnthropicProvider(
    apiKey: $_ENV['ANTHROPIC_API_KEY'],
    defaultModel: 'claude-sonnet-4-20250514',
);
```

### Google (Gemini)

```php
use PapiAI\Google\GoogleProvider;

$provider = new GoogleProvider(
    apiKey: $_ENV['GOOGLE_API_KEY'],
    defaultModel: GoogleProvider::MODEL_3_0_PRO,
);

// Available models
GoogleProvider::MODEL_3_1_PRO   // gemini-3.1-pro
GoogleProvider::MODEL_3_0_PRO   // gemini-3.0-pro
GoogleProvider::MODEL_2_0_FLASH // gemini-2.0-flash-exp
GoogleProvider::MODEL_1_5_PRO   // gemini-1.5-pro
GoogleProvider::MODEL_1_5_FLASH // gemini-1.5-flash
```

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

## Testing

```bash
composer test
```

## License

MIT
