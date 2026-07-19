# Simple Example

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

Or use the fluent builder:

```php
$agent = Agent::build()
    ->provider(new AnthropicProvider(apiKey: $_ENV['ANTHROPIC_API_KEY']))
    ->model('claude-sonnet-4-20250514')
    ->instructions('You are a helpful assistant.')
    ->maxTokens(4096)
    ->temperature(0.7)
    ->create();

$response = $agent->run('Tell me a joke');
echo $response->text;
```
