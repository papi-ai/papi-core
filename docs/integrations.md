# Integrations

Papi supports integration with many external services via workflow nodes. Integrations allow you to connect your workflows to APIs, databases, messaging platforms, and other external services.

## 🔌 Available Integrations

### ✅ Currently Implemented

#### Custom HTTP Integration
You can create custom HTTP integrations using cURL within your own nodes or directly in AI agent tools.

```php
<?php

use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;

class CustomHttpTool implements Node, Tool
{
    private string $id;
    private string $name;
    
    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
    
    public function execute(array $input): array
    {
        $url = $input['url'] ?? '';
        $method = $input['method'] ?? 'GET';
        $headers = $input['headers'] ?? [];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status_code' => $httpCode,
            'response' => $response
        ];
    }
    
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function toArray(): array { return ['id' => $this->id, 'name' => $this->name]; }
    
    public function getToolSchema(): array
    {
        return [
            'name' => 'http_request',
            'description' => 'Make HTTP requests to external APIs',
            'parameters' => [
                'url' => ['type' => 'string', 'required' => true],
                'method' => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'DELETE']],
                'headers' => ['type' => 'array']
            ]
        ];
    }
    
    public function getToolName(): string { return 'http_request'; }
    public function getToolDescription(): string { return 'Make HTTP requests to external APIs'; }
}

// Usage
$httpTool = new CustomHttpTool('http', 'HTTP Tool');
$aiAgent->addTool($httpTool);
```

**Features:**
- Support for GET, POST, PUT, DELETE methods
- Configurable headers and authentication
- JSON and form data support
- Error handling and timeout management

#### Process Integration
Transform and process data between workflow nodes.

```php
<?php

use Papi\Core\Integrations\Process\ProcessNode;

$processNode = new ProcessNode('transform', 'Transform Data');
$processNode->setConfig([
    'operations' => [
        'extract_title' => 'data.title',
        'extract_body' => 'data.body',
        'format_date' => 'data.created_at',
        'calculate_length' => 'strlen(data.content)'
    ]
]);

$workflow->addNode($processNode);
```

**Features:**
- Data extraction and transformation
- Field mapping and renaming
- Basic calculations and formatting
- Array and object manipulation

#### Output Integration
Format and output workflow results.

```php
<?php

use Papi\Core\Nodes\Utility\Output;

$outputNode = new Output('output', 'Output Results', [
    'format' => 'json',
    'pretty_print' => true,
    'include_metadata' => true
]);

$workflow->addNode($outputNode);
```

**Features:**
- Multiple output formats (JSON, XML, CSV)
- Pretty printing and formatting
- Metadata inclusion
- File output support

### 🔄 Planned Integrations

- **Slack**: Send messages, create channels, manage webhooks
- **Discord**: Bot integration, webhook support
- **Email**: SMTP integration for sending emails
- **Database**: MySQL, PostgreSQL, MongoDB support
- **File Storage**: AWS S3, Google Cloud Storage
- **CRM**: Salesforce, HubSpot, Pipedrive
- **Payment**: Stripe, PayPal integration
- **Analytics**: Google Analytics, Mixpanel

## 🛠️ Creating Custom Integrations

### Basic Integration Structure

```php
<?php

use Papi\Core\Node;

class CustomServiceNode extends Node
{
    public function execute(array $input): array
    {
        $config = $this->config;
        $startTime = microtime(true);
        
        try {
            // Your integration logic here
            $result = $this->callExternalService($config, $input);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'success',
                'data' => $result,
                'duration' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'duration' => round($duration, 2)
            ];
        }
    }
    
    private function callExternalService(array $config, array $input): array
    {
        // Implementation specific to your service
        return ['result' => 'success'];
    }
}
```

### Advanced Integration Example: Slack

```php
<?php

use Papi\Core\Node;

class SlackNode extends Node
{
    public function execute(array $input): array
    {
        $config = $this->config;
        $webhookUrl = $config['webhook_url'] ?? '';
        $channel = $config['channel'] ?? '#general';
        $message = $input['message'] ?? $config['message'] ?? '';
        
        if (empty($webhookUrl) || empty($message)) {
            throw new \InvalidArgumentException('Webhook URL and message are required');
        }
        
        $startTime = microtime(true);
        
        try {
            $response = $this->sendToSlack($webhookUrl, $channel, $message);
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'success',
                'data' => $response,
                'message_sent' => $message,
                'channel' => $channel,
                'duration' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'duration' => round($duration, 2)
            ];
        }
    }
    
    private function sendToSlack(string $webhookUrl, string $channel, string $message): array
    {
        $payload = [
            'channel' => $channel,
            'text' => $message,
            'username' => 'Papi Bot',
            'icon_emoji' => ':robot_face:'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $webhookUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Slack API error: $httpCode - $response");
        }
        
        return [
            'status_code' => $httpCode,
            'response' => $response
        ];
    }
}
```

## 🔧 Integration Development Best Practices

### Configuration Management

```php
class MyIntegrationNode extends Node
{
    public function execute(array $input): array
    {
        $config = $this->config;
        
        // Use config for static settings
        $apiKey = $config['api_key'] ?? '';
        $baseUrl = $config['base_url'] ?? '';
        
        // Use input for dynamic data
        $action = $input['action'] ?? $config['action'] ?? '';
        $data = $input['data'] ?? [];
        
        // Validate required configuration
        if (empty($apiKey) || empty($baseUrl)) {
            throw new \InvalidArgumentException('API key and base URL are required');
        }
        
        // Your integration logic here
    }
}
```

### Error Handling

```php
public function execute(array $input): array
{
    $startTime = microtime(true);
    
    try {
        // Integration logic
        $result = $this->performAction($input);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        return [
            'status' => 'success',
            'data' => $result,
            'duration' => round($duration, 2)
        ];
    } catch (\InvalidArgumentException $e) {
        // Handle validation errors
        return [
            'status' => 'error',
            'error' => 'Invalid parameters: ' . $e->getMessage(),
            'duration' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    } catch (\RuntimeException $e) {
        // Handle runtime errors
        return [
            'status' => 'error',
            'error' => 'Service error: ' . $e->getMessage(),
            'duration' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    } catch (\Exception $e) {
        // Handle unexpected errors
        return [
            'status' => 'error',
            'error' => 'Unexpected error: ' . $e->getMessage(),
            'duration' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }
}
```

### Testing Integrations

```php
<?php

use PHPUnit\Framework\TestCase;

class SlackNodeTest extends TestCase
{
    private SlackNode $node;
    
    protected function setUp(): void
    {
        $this->node = new SlackNode('test', 'Test Slack');
        $this->node->setConfig([
            'webhook_url' => 'https://hooks.slack.com/test',
            'channel' => '#test'
        ]);
    }
    
    public function testSuccessfulMessage(): void
    {
        $result = $this->node->execute(['message' => 'Test message']);
        
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('message_sent', $result);
        $this->assertEquals('Test message', $result['message_sent']);
    }
    
    public function testMissingMessage(): void
    {
        $result = $this->node->execute([]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('message', $result['error']);
    }
}
```

## 🔗 Integration Patterns

### AI Agent with Custom Tool Pattern

```php
<?php

// AI Agent -> Custom Tool -> Output
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;

$aiAgent = new AIAgent('assistant', 'Data Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->addTool(new CustomHttpTool('http', 'HTTP Tool'));

$outputNode = new Output('output', 'Output Results', [
    'format' => 'json',
    'pretty_print' => true
]);

$workflow->addNode($aiAgent);
$workflow->addNode($outputNode);

$workflow->addConnection(new Connection('assistant', 'output'));
```

### Notification Pattern

```php
// Process -> AI Agent -> Slack
$processNode = new ProcessNode('process', 'Process Data');
$aiAgent = new AIAgent('analyze', 'Analyze Data');
$slackNode = new SlackNode('notify', 'Send Notification');

$workflow->addNode($processNode);
$workflow->addNode($aiAgent);
$workflow->addNode($slackNode);

$workflow->addConnection(new Connection('process', 'analyze'));
$workflow->addConnection(new Connection('analyze', 'notify'));
```

## 🔗 Related Documentation

- [Getting Started](./getting-started.md) - Basic workflow setup
- [AI Agents](./ai-agents.md) - Working with AI agents
- [Developer Guide](./developer-guide.md) - Creating custom integrations
- [API Reference](./api-reference.md) - Complete API documentation
- [Workflow Patterns](./workflow-patterns.md) - Common integration patterns

---

**< Previous**: [AI Agents](./ai-agents.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Developer Guide](./developer-guide.md) 