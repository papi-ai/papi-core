# AI Agents in Papi

Papi supports AI-powered agents as workflow nodes that can interact with LLMs (Large Language Models) and use tools to perform complex tasks.

## 🤖 Overview

AI Agents are special nodes that:
- **Accept input and context** from the workflow
- **Call LLMs** (OpenAI, Anthropic, etc.) with structured prompts
- **Use tools** to fetch data, perform calculations, or interact with external services
- **Return structured results** to the workflow for further processing

## 🚀 Basic Usage

### Simple AI Agent

```php
<?php

use Papi\Core\Agents\AIAgent;
use Papi\Core\Workflow;

// Create AI agent
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that provides clear and concise answers.');

// Add to workflow
$workflow = new Workflow('ai_workflow');
$workflow->addNode($aiAgent);

// Execute
$execution = $workflow->execute([
    'query' => 'What is the capital of France?'
]);

$result = $execution->getOutputData();
echo $result['data']['response'];
```

### AI Agent with Tools

```php
<?php

use Papi\Core\Agents\AIAgent;
use Papi\Core\Tools\HttpTool;
use Papi\Core\Tools\MathTool;

// Create tools
$httpTool = new HttpTool();
$mathTool = new MathTool();

// Create AI agent with tools
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can fetch data and perform calculations.')
    ->addTool($httpTool)
    ->addTool($mathTool);

// The AI agent can now use these tools when needed
$execution = $workflow->execute([
    'query' => 'What is the current weather in London and what is 15 squared?'
]);
```

## 🔧 Configuration Options

### Model Selection

```php
$aiAgent->setModel('gpt-4');           // Use GPT-4
$aiAgent->setModel('gpt-3.5-turbo');   // Use GPT-3.5 Turbo
$aiAgent->setModel('claude-3-sonnet'); // Use Claude (when supported)
```

### System Prompts

```php
// General assistant
$aiAgent->setSystemPrompt('You are a helpful assistant.');

// Specialized assistant
$aiAgent->setSystemPrompt('You are a data analyst. Always provide insights and recommendations based on data.');

// Task-specific assistant
$aiAgent->setSystemPrompt('You are a customer support agent. Be polite, helpful, and concise.');
```

### Tool Integration

```php
// Add built-in tools
$aiAgent->addTool(new HttpTool());
$aiAgent->addTool(new MathTool());

// Add custom tools
$aiAgent->addTool(new CustomTool());
$aiAgent->addTool(new DatabaseSearchTool());
```

## 🛠️ Available Tools

### Built-in Tools

#### HTTP Tool
```php
use Papi\Core\Tools\HttpTool;

$httpTool = new HttpTool();
$aiAgent->addTool($httpTool);

// AI can now make HTTP requests like:
// "Fetch the latest news from the API"
// "Get weather data for New York"
```

#### Math Tool
```php
use Papi\Core\Tools\MathTool;

$mathTool = new MathTool();
$aiAgent->addTool($mathTool);

// AI can now perform calculations like:
// "What is the square root of 144?"
// "Calculate 25 * 13 + 7"
```

### Custom Tools

See [Developer Guide](./developer-guide.md) for creating custom tools.

## 📊 Response Format

AI Agent responses include detailed information:

```php
$result = $aiAgent->execute(['query' => 'What is 10 squared?']);

// Response structure:
[
    'status' => 'success',
    'data' => [
        'response' => '10 squared is 100.',
        'tools_used' => ['math_calculation'],
        'tool_results' => [
            [
                'tool' => 'math_calculation',
                'args' => ['operation' => 'pow', 'a' => 10, 'b' => 2],
                'result' => ['result' => 100]
            ]
        ],
        'input' => ['query' => 'What is 10 squared?']
    ],
    'duration' => 1250.5,        // Execution time in milliseconds
    'model' => 'gpt-3.5-turbo',  // Model used
    'tokens_used' => 150         // Token usage
]
```

## 🔄 Memory and Context

### Current Limitations
- **No Persistent Memory**: Each execution is independent
- **No Conversation History**: No built-in conversation memory
- **No Context Persistence**: Context is not maintained between executions

### Planned Features
- **Conversation Memory**: Maintain conversation history across executions
- **Context Persistence**: Store and retrieve context for long-running workflows
- **Memory Management**: Configurable memory limits and cleanup

### Workarounds

You can implement basic memory by passing conversation history in the input:

```php
$conversationHistory = [
    ['role' => 'user', 'content' => 'Hello'],
    ['role' => 'assistant', 'content' => 'Hi! How can I help you?']
];

$execution = $workflow->execute([
    'query' => 'What did we talk about earlier?',
    'conversation_history' => $conversationHistory
]);
```

## 🧪 Testing AI Agents

### Using Mock Client

```php
<?php

use Papi\Core\Integrations\MockOpenAIClient;

// Use mock client for testing
$mockClient = new MockOpenAIClient();
$aiAgent->setOpenAIClient($mockClient);

// Test without making real API calls
$result = $aiAgent->execute(['query' => 'Test query']);
```

### Testing Tool Integration

```php
<?php

// Test that tools are properly integrated
$httpTool = new HttpTool();
$aiAgent->addTool($httpTool);

$result = $aiAgent->execute(['query' => 'Fetch data from https://api.example.com']);

// Verify tool was used
$this->assertContains('http_request', $result['data']['tools_used']);
```

## ⚠️ Best Practices

### Prompt Engineering
- **Be Specific**: Provide clear, specific instructions in system prompts
- **Set Boundaries**: Define what the AI should and shouldn't do
- **Use Examples**: Include examples in prompts for complex tasks
- **Iterate**: Test and refine prompts for better results

### Tool Usage
- **Validate Tools**: Ensure tools are properly validated and tested
- **Error Handling**: Implement proper error handling in custom tools
- **Rate Limiting**: Be mindful of API rate limits for external tools
- **Security**: Validate all inputs and outputs from tools

### Performance
- **Model Selection**: Choose appropriate models for your use case
- **Token Management**: Monitor token usage to control costs
- **Caching**: Cache responses when appropriate
- **Batch Processing**: Process multiple queries efficiently

## 🔗 Related Documentation

- [Getting Started](./getting-started.md) - Basic setup and usage
- [Workflow Patterns](./workflow-patterns.md) - AI agent workflow patterns
- [Developer Guide](./developer-guide.md) - Creating custom tools
- [Integrations](./integrations.md) - Available integrations
- [API Reference](./api-reference.md) - Complete API documentation

---

**< Previous**: [Workflow Patterns](./workflow-patterns.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Integrations](./integrations.md) 