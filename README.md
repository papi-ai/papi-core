# Papi Core

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Release](https://img.shields.io/github/v/release/papi-ai/papi-core)](https://github.com/papi-ai/papi-core/releases)
[![Packagist](https://img.shields.io/packagist/v/papi-ai/papi-core)](https://packagist.org/packages/papi-ai/papi-core)
[![Downloads](https://img.shields.io/packagist/dt/papi-ai/papi-core)](https://packagist.org/packages/papi-ai/papi-core)
[![CI](https://github.com/papi-ai/papi-core/workflows/CI/badge.svg)](https://github.com/papi-ai/papi-core/actions?query=workflow%3ACI)
[![Code Coverage](https://codecov.io/gh/papi-ai/papi-core/branch/main/graph/badge.svg)](https://codecov.io/gh/papi-ai/papi-core)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-brightgreen.svg)](https://www.php-fig.org/psr/psr-12/)

**Papi Core** is the decoupled PHP library powering [papi-ai](https://github.com/papi-ai), an open-source, n8n-inspired AI workflow automation platform.

Build powerful AI-powered workflows with a modern, extensible PHP engine that supports AI agents, custom tools, and seamless integrations.

## ✨ Features

- **🤖 AI Agent Support**: Integrate LLMs (OpenAI, Anthropic) with tool-calling capabilities and memory
- **🔧 Extensible Tool System**: Create custom tools for AI agents to use
- **🔌 Integration Framework**: Build nodes for external services and APIs
- **⚡ Modern Workflow Engine**: Compose, execute, and extend workflows with nodes and connections
- **🚀 Trigger System**: Event-driven workflow initiation with chat, email, and manual triggers
- **🏗️ Framework Agnostic**: Use standalone or with Laravel/Symfony bundles
- **🧪 Testing Ready**: Comprehensive testing utilities and mock clients

## 🚀 Quick Start

### Requirements

- PHP 8.1 or higher
- Composer
- OpenAI API key (for AI features)

### Installation

```bash
composer require papi-ai/papi-core
```

### Basic Workflow Example

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Integrations\OpenAIClient;

// Create AI agent
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can help with various tasks.');

// Create workflow
$workflow = new Workflow('demo_workflow');
$workflow->addNode($aiAgent);

// Execute workflow
$execution = $workflow->execute([
    'query' => 'What is the square root of 144?'
]);

echo json_encode($execution->getOutputData(), JSON_PRETTY_PRINT);
```

### AI Agent with Memory

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Memory\InMemory;
use Papi\Core\Nodes\Utility\Output;
use Papi\Core\Integrations\MockOpenAIClient;

// Create memory node for conversation context
$memoryNode = new InMemory('memory1', 'Conversation Memory');

// Create output node for formatting results
$outputNode = new Output('output1', 'Output Results', [
    'format' => 'json',
    'pretty_print' => true
]);

// Create AI agent with memory
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can maintain conversation context.')
    ->setMemory($memoryNode);

// Use mock client for testing
$mockClient = new MockOpenAIClient([
    'Tell me about yourself' => 'I am an AI assistant designed to help you with various tasks and maintain conversation context.'
]);
$aiAgent->setLLMClient($mockClient);

// Create workflow
$workflow = new Workflow('data_workflow');
$workflow->addNode($aiAgent);
$workflow->addNode($outputNode);

// Connect AI agent to output
$workflow->addConnection(new Connection('assistant', 'output1'));

// Execute workflow with conversation history
$execution = $workflow->execute(['query' => 'Fetch and analyze the data']);
$result = $execution->getOutputData();

echo $result['data'];
```

### Trigger-Driven Workflow

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Triggers\ChatTriggerNode;
use Papi\Core\Triggers\EmailTriggerNode;
use Papi\Core\Nodes\AI\AIAgent;

// Create trigger nodes
$chatTrigger = new ChatTriggerNode('chat', 'Chat Trigger', [
    'message' => 'Hello, I need help with my order',
    'sender' => 'user123',
    'channel' => 'support'
]);

$emailTrigger = new EmailTriggerNode('email', 'Email Trigger', [
    'subject' => 'New support ticket',
    'body' => 'A customer needs assistance',
    'sender' => 'noreply@example.com',
    'recipients' => ['support@example.com']
]);

// Create AI agent to process triggers
$aiAgent = new AIAgent('processor', 'Trigger Processor');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('Process incoming triggers and provide appropriate responses.');

// Create workflow
$workflow = new Workflow('trigger_workflow');
$workflow->addNode($chatTrigger);
$workflow->addNode($emailTrigger);
$workflow->addNode($aiAgent);

// Connect triggers to AI agent
$workflow->addConnection(new Connection('chat', 'processor'));
$workflow->addConnection(new Connection('email', 'processor'));

// Execute workflow (triggers don't need input)
$execution = $workflow->execute();
$result = $execution->getOutputData();
```

## 🎯 Provider-Agnostic AI Architecture

Papi Core uses a clean, provider-agnostic architecture that decouples AI agents from specific LLM providers:

### **LLM Client Interface:**
- **`LLMClientInterface`**: Abstract interface for any LLM provider
- **Provider-agnostic**: Works with OpenAI, Anthropic, or any custom provider
- **Easy switching**: Change providers without changing agent code
- **Consistent API**: Same interface across all providers

### **Supported Providers:**
- **OpenAI**: GPT-3.5, GPT-4, and other OpenAI models
- **Anthropic**: Claude models (via custom client)
- **Custom Providers**: Easy to implement for any LLM service
- **Mock Clients**: For testing and development

### **Example: Multi-Provider Setup**

```php
<?php

use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Integrations\LLMClientInterface;

// Create different LLM clients
$openAIClient = new RealOpenAIClient('your-api-key');
$anthropicClient = new AnthropicClient('your-api-key');
$customClient = new CustomLLMClient();

// Create AI agents with different providers
$openAIAgent = new AIAgent('openai_agent', 'OpenAI Agent');
$openAIAgent->setModel('gpt-4')
    ->setLLMClient($openAIClient);

$anthropicAgent = new AIAgent('anthropic_agent', 'Anthropic Agent');
$anthropicAgent->setModel('claude-3-sonnet')
    ->setLLMClient($anthropicClient);

$customAgent = new AIAgent('custom_agent', 'Custom Agent');
$customAgent->setModel('custom-model')
    ->setLLMClient($customClient);
```

## 🎯 Interface-Based Node System

Papi Core uses a clean interface-based system where nodes can implement multiple capabilities:

### **Core Interfaces:**

- **`Node`**: Base interface for all nodes
- **`Tool`**: Nodes that can be used as tools by AI agents
- **`Memory`**: Nodes that can store conversation context
- **`Trigger`**: Nodes that can initiate workflows

### **Example: Multi-Capability Node**

```php
<?php

namespace Papi\Core\Nodes\Integration\Google;

use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;
use Papi\Core\Nodes\Memory;

class Sheets implements Node, Tool, Memory
{
    public function execute(array $input): array
    {
        // Handle both tool calls and memory operations
        if (isset($input['operation'])) {
            return $this->handleToolOperation($input);
        }
        return $this->handleMemoryOperation($input);
    }
    
    // Tool interface methods
    public function getToolSchema(): array
    {
        return [
            'name' => 'google_sheets',
            'description' => 'Read and write data to Google Sheets',
            'parameters' => [
                'operation' => ['type' => 'string', 'enum' => ['read', 'write']],
                'spreadsheet_id' => ['type' => 'string', 'required' => true],
                'range' => ['type' => 'string', 'required' => true]
            ]
        ];
    }
    
    public function getToolName(): string { return 'google_sheets'; }
    public function getToolDescription(): string { return 'Read and write data to Google Sheets'; }
    
    // Memory interface methods
    public function addMessage(string $role, string $content, array $metadata = []): void
    {
        // Store conversation in a dedicated sheet
        $this->appendToSheet('conversation_log', [
            'timestamp' => time(),
            'role' => $role,
            'content' => $content
        ]);
    }
    
    public function getMessages(int $limit = null): array { /* ... */ }
    public function clear(): void { /* ... */ }
    public function getContext(int $maxTokens = 4000): array { /* ... */ }
}
```

### **Using Nodes with AI Agents:**

```php
<?php

use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Memory\InMemory;

// Create memory node
$memoryNode = new InMemory('memory1', 'Conversation Memory');

// Create AI agent with interface-based capabilities
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setMemory($memoryNode);  // Type-safe: only Memory nodes
```

## 🛠️ Creating Custom Tools

```php
<?php

use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;

class WeatherTool implements Node, Tool
{
    public function getId(): string
    {
        return 'weather_tool';
    }

    public function getName(): string
    {
        return 'Weather Tool';
    }

    public function execute(array $input): array
    {
        $location = $input['location'] ?? '';
        $units = $input['units'] ?? 'celsius';
        
        if (empty($location)) {
            throw new \InvalidArgumentException('Location is required');
        }
        
        // Your weather API logic here
        $weather = $this->fetchWeather($location, $units);
        
        return [
            'location' => $location,
            'temperature' => $weather['temp'],
            'conditions' => $weather['conditions'],
            'units' => $units
        ];
    }

    public function getToolSchema(): array
    {
            'name' => 'get_weather',
            'description' => 'Get current weather information for a location',
            'parameters' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'City name or coordinates',
                    'required' => true
                ],
                'units' => [
                    'type' => 'string',
                    'description' => 'Temperature units (celsius/fahrenheit)',
                    'default' => 'celsius'
                ]
            ]
        ];
    }

    public function getToolName(): string
    {
        return 'get_weather';
    }

    public function getToolDescription(): string
    {
        return 'Get current weather information for a location';
    }
}

// Use the custom tool
$weatherTool = new WeatherTool('weather1', 'Weather Tool');
$aiAgent->addTool($weatherTool);
```

## 🚀 Creating Trigger Nodes

Triggers are entry points to workflows that can be initiated by external events. Here's how to create a custom trigger:

```php
<?php

use Papi\Core\Triggers\BaseTriggerNode;

class WebhookTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'webhook';
    }
    
    protected function processTrigger(): array
    {
        $payload = $this->triggerConfig['payload'] ?? [];
        
        return [
            'type' => 'webhook_event',
            'data' => $payload,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        if (empty($this->triggerConfig['payload'])) {
            throw new \InvalidArgumentException('Webhook trigger requires payload');
        }
        return true;
    }
}

// Use the custom trigger
$webhookTrigger = new WebhookTriggerNode('webhook', 'Webhook Trigger', [
    'payload' => ['event' => 'user_registered']
]);
$workflow->addNode($webhookTrigger);
```

## 🔌 Creating Custom Integrations

Integrations are workflow nodes that connect to external services. Here's how to create a custom integration:

```php
<?php

use Papi\Core\Node;

class SlackNode extends Node
{
    public function execute(array $input): array
    {
        $config = $this->config;
        $webhookUrl = $config['webhook_url'] ?? '';
        $message = $input['message'] ?? $config['message'] ?? '';
        
        if (empty($webhookUrl) || empty($message)) {
            throw new \InvalidArgumentException('Webhook URL and message are required');
        }
        
        // Send message to Slack
        $response = $this->sendToSlack($webhookUrl, $message);
        
        return [
            'status' => 'success',
            'data' => $response,
            'message_sent' => $message
        ];
    }
    
    private function sendToSlack(string $webhookUrl, string $message): array
    {
        // Slack webhook implementation
        $payload = ['text' => $message];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $webhookUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status_code' => $httpCode,
            'response' => $response
        ];
    }
}

// Use the custom integration
$slackNode = new SlackNode('notify', 'Slack Notification');
$slackNode->setConfig(['webhook_url' => 'https://hooks.slack.com/...']);
$workflow->addNode($slackNode);
```

## 📚 Documentation

📖 **[Documentation Index](./docs/index.md)** - Complete documentation overview and navigation

**Getting Started:**
- **[Getting Started](./docs/getting-started.md)** - Installation and basic usage
- **[Workflow Patterns](./docs/workflow-patterns.md)** - Understanding workflow design
- **[AI Agents](./docs/ai-agents.md)** - Working with AI agents and tools

**Development:**
- **[Integrations](./docs/integrations.md)** - Available integrations and creating custom ones
- **[Developer Guide](./docs/developer-guide.md)** - Creating custom tools, integrations, and testing
- **[Templates](./docs/templates.md)** - Reusable workflow patterns

**Reference & Support:**
- **[API Reference](./docs/api-reference.md)** - Complete API documentation
- **[Troubleshooting](./docs/troubleshooting.md)** - Common issues and debugging techniques

## 🏗️ Architecture

### Core Components

- **Workflow**: Main container for workflow logic and execution
- **Node**: Individual processing units (AI agents, integrations, etc.)
- **Connection**: Links between nodes with data transformation
- **Execution**: Workflow execution engine with error handling
- **Tools**: Functions that AI agents can call
- **Integrations**: External service connectors

### Workflow Execution

```php
// Create workflow
$workflow = new Workflow('my_workflow');

// Add nodes
$workflow->addNode($node1);
$workflow->addNode($node2);

// Connect nodes
$workflow->addConnection(new Connection('node1', 'node2'));

// Execute with input data
$execution = $workflow->execute(['input' => 'data']);

// Get results
$output = $execution->getOutputData();
$nodeResults = $execution->getNodeResults();
```

## 🧪 Testing

Papi Core includes comprehensive testing utilities:

```php
<?php

use Papi\Core\Integrations\MockOpenAIClient;

// Use mock client for testing
$mockClient = new MockOpenAIClient();
$aiAgent->setLLMClient($mockClient);

// Test workflow execution
$execution = $workflow->execute(['test' => 'data']);
$this->assertEquals('success', $execution->getStatus());
```

## 🚧 Roadmap

### Current Features
- ✅ Core workflow engine
- ✅ AI agent support with tool-calling and memory
- ✅ LLM node for basic text generation
- ✅ Memory and context management
- ✅ Basic integrations (Process, Output)
- ✅ Trigger system (chat, email, manual)
- ✅ Mock OpenAI client for testing

### Planned Features
- 🔄 Parallel workflow execution
- 🔄 Conditional workflow logic
- 🔄 Loop workflows
- 🔄 Plugin discovery system
- 🔄 More built-in integrations (Slack, Discord, databases)
- 🔄 Workflow templates and sharing
- 🔄 Advanced AI agent features (enhanced memory, context)

## 🤝 Community & Support

### Ecosystem Projects

Papi Core is part of the larger [papi-ai](https://github.com/papi-ai) ecosystem:

- **[papi-ui](https://github.com/papi-ai/papi-ui)** - Laravel-based web interface
- **[papi-symfony-bundle](https://github.com/papi-ai/papi-symfony-bundle)** - Symfony integration
- **[papi-plugins](https://github.com/papi-ai/papi-plugins)** - Community plugins
- **[papi-website](https://github.com/papi-ai/papi-website)** - Documentation and landing page

### Getting Help

- 📖 [Documentation](./docs/) - Comprehensive guides and API reference
- 🐛 [Issues](https://github.com/papi-ai/papi-core/issues) - Report bugs and request features
- 💬 [Discussions](https://github.com/papi-ai/papi-core/discussions) - Ask questions and share ideas

### Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

- 🐛 Bug reports and feature requests
- 💻 Code contributions and pull requests
- 📚 Documentation improvements
- 🧪 Test coverage additions

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Inspired by [n8n](https://n8n.io) workflow automation
- Built with modern PHP practices and standards
- Community-driven development and feedback 