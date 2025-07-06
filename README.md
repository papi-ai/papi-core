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

- **🤖 AI Agent Support**: Integrate LLMs (OpenAI, Anthropic) with tool-calling capabilities
- **🔧 Extensible Tool System**: Create custom tools for AI agents to use
- **🔌 Integration Framework**: Build nodes for external services and APIs
- **⚡ Modern Workflow Engine**: Compose, execute, and extend workflows with nodes and connections
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
use Papi\Core\Agents\AIAgent;
use Papi\Core\Tools\HttpTool;
use Papi\Core\Tools\MathTool;
use Papi\Core\Integrations\OpenAIClient;

// Create tools for the AI agent
$httpTool = new HttpTool();
$mathTool = new MathTool();

// Create AI agent with tools
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can fetch data and perform calculations.')
    ->addTool($httpTool)
    ->addTool($mathTool);

// Create workflow
$workflow = new Workflow('demo_workflow');
$workflow->addNode($aiAgent);

// Execute workflow
$execution = $workflow->execute([
    'query' => 'What is the square root of 144?'
]);

echo json_encode($execution->getOutputData(), JSON_PRETTY_PRINT);
```

### AI Agent with HTTP Integration

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Integrations\Http\HttpNode;
use Papi\Core\Integrations\Process\ProcessNode;

// Create HTTP node to fetch data
$httpNode = new HttpNode('fetch', 'Fetch Data');
$httpNode->setConfig([
    'method' => 'GET',
    'url' => 'https://jsonplaceholder.typicode.com/posts/1',
]);

// Create process node to transform data
$processNode = new ProcessNode('process', 'Process Data');
$processNode->setConfig([
    'operations' => [
        'extract_title' => 'data.title',
        'extract_body' => 'data.body',
    ]
]);

// Create workflow
$workflow = new Workflow('data_workflow');
$workflow->addNode($httpNode);
$workflow->addNode($processNode);
$workflow->addConnection(new Connection('fetch', 'process'));

// Execute workflow
$execution = $workflow->execute();
$result = $execution->getOutputData();
```

## 🛠️ Creating Custom Tools

Tools are functions that AI agents can call to perform specific tasks. Here's how to create a custom tool:

```php
<?php

use Papi\Core\Tools\ToolInterface;

class WeatherTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_weather';
    }

    public function getDescription(): string
    {
        return 'Get current weather information for a location';
    }

    public function getParameters(): array
    {
        return [
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
        ];
    }

    public function execute(array $params): array
    {
        $location = $params['location'];
        $units = $params['units'] ?? 'celsius';
        
        // Your weather API logic here
        $weather = $this->fetchWeather($location, $units);
        
        return [
            'location' => $location,
            'temperature' => $weather['temp'],
            'conditions' => $weather['conditions'],
            'units' => $units
        ];
    }

    public function validate(array $params): bool
    {
        return isset($params['location']) && !empty($params['location']);
    }
}

// Use the custom tool
$weatherTool = new WeatherTool();
$aiAgent->addTool($weatherTool);
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
$aiAgent->setOpenAIClient($mockClient);

// Test workflow execution
$execution = $workflow->execute(['test' => 'data']);
$this->assertEquals('success', $execution->getStatus());
```

## 🚧 Roadmap

### Current Features
- ✅ Core workflow engine
- ✅ AI agent support with tool-calling
- ✅ HTTP and Math tools
- ✅ Basic integrations (HTTP, Process, Output)
- ✅ Mock OpenAI client for testing

### Planned Features
- 🔄 Parallel workflow execution
- 🔄 Conditional workflow logic
- 🔄 Loop workflows
- 🔄 Plugin discovery system
- 🔄 More built-in integrations (Slack, Discord, databases)
- 🔄 Workflow templates and sharing
- 🔄 Advanced AI agent features (memory, context)

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