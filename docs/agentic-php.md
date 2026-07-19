# Agentic PHP

Agents become powerful when they can use tools, loop autonomously, and produce structured output.

## Tool Calling

Define tools as simple functions:

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

## Class-based Tools with Attributes

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

$response->data['sentiment'];   // 'positive'
$response->data['confidence'];  // 0.95
$response->data['keywords'];    // ['great', 'recommend']
```

## Schema Types

```php
Schema::string()                         // String values
Schema::string()->min(1)->max(100)       // Length constraints
Schema::string()->pattern('/regex/')     // Regex pattern

Schema::number()                         // Float values
Schema::integer()                        // Integer values
Schema::number()->min(0)->max(100)       // Range constraints

Schema::boolean()                        // Boolean values

Schema::array(Schema::string())          // Array of strings
Schema::array($item)->minItems(1)->maxItems(10)

Schema::object([                         // Object with properties
    'name' => Schema::string(),
    'age' => Schema::integer()->optional(),
])

Schema::enum(['a', 'b', 'c'])           // Enum values

// Modifiers work on any type
->nullable()           // Allow null
->optional()           // Not required in objects
->default('value')     // Default value
->description('...')   // Hint for the LLM
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
        'text'        => echo $event->text,
        'tool_call'   => echo "Calling: {$event->tool}\n",
        'tool_result' => echo "Result: " . json_encode($event->result) . "\n",
        'done'        => echo "\nComplete!\n",
        'error'       => echo "Error: {$event->error}\n",
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

## Middleware

Composable middleware for production-grade agents:

```php
use PapiAI\Core\Middleware\RetryMiddleware;
use PapiAI\Core\Middleware\RateLimitMiddleware;
use PapiAI\Core\Middleware\LoggingMiddleware;
use PapiAI\Core\Middleware\CacheMiddleware;

$agent = Agent::build()
    ->provider($provider)
    ->model('claude-sonnet-4-20250514')
    ->addMiddleware(new RetryMiddleware(maxRetries: 3))
    ->addMiddleware(new RateLimitMiddleware(maxRequests: 60, perSeconds: 60))
    ->addMiddleware(new LoggingMiddleware($psrLogger))
    ->addMiddleware(new CacheMiddleware($psrCache, ttl: 3600))
    ->create();
```

## Conversations

Build conversations manually:

```php
use PapiAI\Core\Message;
use PapiAI\Core\Conversation;

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
