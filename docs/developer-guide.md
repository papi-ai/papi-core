# Developer Guide

This guide covers advanced development topics for Papi Core, including creating custom tools, integrations, testing strategies, and best practices.

## 🛠️ Creating Custom Tools

Tools are functions that AI agents can call to perform specific tasks. They implement the `ToolInterface` and provide a standardized way for AI agents to interact with external services or perform calculations.

### Tool Interface Overview

```php
<?php

use Papi\Core\Tools\ToolInterface;

interface ToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getParameters(): array;
    public function execute(array $params): array;
    public function validate(array $params): bool;
}
```

### Step-by-Step Tool Creation

#### 1. Basic Tool Structure

```php
<?php

use Papi\Core\Tools\ToolInterface;

class DatabaseSearchTool implements ToolInterface
{
    public function getName(): string
    {
        return 'database_search';
    }

    public function getDescription(): string
    {
        return 'Search the database for records matching criteria';
    }

    public function getParameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Search query',
                'required' => true
            ],
            'table' => [
                'type' => 'string',
                'description' => 'Database table to search',
                'required' => true
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of results',
                'default' => 10
            ]
        ];
    }

    public function execute(array $params): array
    {
        // Validate parameters
        if (!$this->validate($params)) {
            throw new \InvalidArgumentException('Invalid parameters');
        }

        // Execute the search
        $results = $this->performSearch(
            $params['query'],
            $params['table'],
            $params['limit'] ?? 10
        );

        return [
            'status' => 'success',
            'results' => $results,
            'count' => count($results),
            'query' => $params['query']
        ];
    }

    public function validate(array $params): bool
    {
        return isset($params['query']) && 
               !empty($params['query']) && 
               isset($params['table']) && 
               !empty($params['table']);
    }

    private function performSearch(string $query, string $table, int $limit): array
    {
        // Your database search implementation here
        // This is just an example
        return [
            ['id' => 1, 'name' => 'Example Record 1'],
            ['id' => 2, 'name' => 'Example Record 2']
        ];
    }
}
```

#### 2. Advanced Tool with Error Handling

```php
<?php

use Papi\Core\Tools\ToolInterface;

class EmailSenderTool implements ToolInterface
{
    public function getName(): string
    {
        return 'send_email';
    }

    public function getDescription(): string
    {
        return 'Send an email to specified recipients';
    }

    public function getParameters(): array
    {
        return [
            'to' => [
                'type' => 'array',
                'description' => 'List of email addresses to send to',
                'required' => true
            ],
            'subject' => [
                'type' => 'string',
                'description' => 'Email subject line',
                'required' => true
            ],
            'body' => [
                'type' => 'string',
                'description' => 'Email body content',
                'required' => true
            ],
            'from' => [
                'type' => 'string',
                'description' => 'Sender email address',
                'default' => 'noreply@example.com'
            ]
        ];
    }

    public function execute(array $params): array
    {
        try {
            if (!$this->validate($params)) {
                throw new \InvalidArgumentException('Invalid email parameters');
            }

            $sentCount = 0;
            $errors = [];

            foreach ($params['to'] as $email) {
                try {
                    $this->sendSingleEmail(
                        $email,
                        $params['subject'],
                        $params['body'],
                        $params['from'] ?? 'noreply@example.com'
                    );
                    $sentCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to send to $email: " . $e->getMessage();
                }
            }

            return [
                'status' => 'success',
                'sent_count' => $sentCount,
                'total_recipients' => count($params['to']),
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    public function validate(array $params): bool
    {
        return isset($params['to']) && 
               is_array($params['to']) && 
               !empty($params['to']) &&
               isset($params['subject']) && 
               !empty($params['subject']) &&
               isset($params['body']) && 
               !empty($params['body']);
    }

    private function sendSingleEmail(string $to, string $subject, string $body, string $from): void
    {
        // Your email sending implementation here
        // This could use PHPMailer, SwiftMailer, or any email service
        mail($to, $subject, $body, "From: $from");
    }
}
```

### Using Custom Tools

```php
<?php

use Papi\Core\Agents\AIAgent;

// Create AI agent with custom tools
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You can search the database and send emails.')
    ->addTool(new DatabaseSearchTool())
    ->addTool(new EmailSenderTool());

// The AI agent can now use these tools
$execution = $workflow->execute([
    'query' => 'Search for users named John and send them a welcome email'
]);
```

## 🔌 Creating Custom Integrations

Integrations are workflow nodes that connect to external services. They extend the base `Node` class and provide a standardized way to interact with APIs, databases, and other services.

### Integration Structure

```php
<?php

use Papi\Core\Node;

abstract class BaseIntegration extends Node
{
    protected function makeHttpRequest(string $method, string $url, array $headers = [], $body = null): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ]);

        if ($body && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }

        return [
            'status_code' => $httpCode,
            'body' => $response,
            'headers' => $headers
        ];
    }

    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return $formatted;
    }
}
```

### Example: Slack Integration

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

### Example: Database Integration

```php
<?php

use Papi\Core\Node;

class DatabaseNode extends Node
{
    private \PDO $pdo;
    
    public function __construct(string $id, string $name)
    {
        parent::__construct($id, $name);
        $this->initializeDatabase();
    }
    
    public function execute(array $input): array
    {
        $config = $this->config;
        $operation = $config['operation'] ?? $input['operation'] ?? 'select';
        $table = $config['table'] ?? $input['table'] ?? '';
        $query = $config['query'] ?? $input['query'] ?? '';
        
        if (empty($table)) {
            throw new \InvalidArgumentException('Table name is required');
        }
        
        $startTime = microtime(true);
        
        try {
            $result = match($operation) {
                'select' => $this->executeSelect($table, $query),
                'insert' => $this->executeInsert($table, $input['data'] ?? []),
                'update' => $this->executeUpdate($table, $input['data'] ?? [], $input['where'] ?? []),
                'delete' => $this->executeDelete($table, $input['where'] ?? []),
                default => throw new \InvalidArgumentException("Unknown operation: $operation")
            };
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'success',
                'data' => $result,
                'operation' => $operation,
                'table' => $table,
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
    
    private function initializeDatabase(): void
    {
        $dsn = $_ENV['DATABASE_DSN'] ?? 'mysql:host=localhost;dbname=test';
        $username = $_ENV['DATABASE_USERNAME'] ?? 'root';
        $password = $_ENV['DATABASE_PASSWORD'] ?? '';
        
        $this->pdo = new \PDO($dsn, $username, $password);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    
    private function executeSelect(string $table, string $query = ''): array
    {
        $sql = $query ?: "SELECT * FROM $table";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function executeInsert(string $table, array $data): array
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'affected_rows' => $stmt->rowCount()
        ];
    }
    
    private function executeUpdate(string $table, array $data, array $where): array
    {
        $setClause = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($col) => "$col = :where_$col", array_keys($where)));
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        
        $params = array_merge($data, array_map(fn($col) => $where[$col], array_keys($where)));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return ['affected_rows' => $stmt->rowCount()];
    }
    
    private function executeDelete(string $table, array $where): array
    {
        $whereClause = implode(' AND ', array_map(fn($col) => "$col = :$col", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
        
        return ['affected_rows' => $stmt->rowCount()];
    }
}
```

## 🧪 Testing Strategies

### Testing Tools

```php
<?php

use PHPUnit\Framework\TestCase;
use Papi\Core\Tools\CustomTool;

class CustomToolTest extends TestCase
{
    private CustomTool $tool;
    
    protected function setUp(): void
    {
        $this->tool = new CustomTool();
    }
    
    public function testToolName(): void
    {
        $this->assertEquals('custom_tool', $this->tool->getName());
    }
    
    public function testToolDescription(): void
    {
        $this->assertNotEmpty($this->tool->getDescription());
    }
    
    public function testToolParameters(): void
    {
        $parameters = $this->tool->getParameters();
        $this->assertIsArray($parameters);
        $this->assertNotEmpty($parameters);
    }
    
    public function testToolValidation(): void
    {
        $validParams = ['param1' => 'value1'];
        $this->assertTrue($this->tool->validate($validParams));
        
        $invalidParams = [];
        $this->assertFalse($this->tool->validate($invalidParams));
    }
    
    public function testToolExecution(): void
    {
        $params = ['param1' => 'value1'];
        $result = $this->tool->execute($params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    
    public function testToolExecutionWithInvalidParams(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->tool->execute([]);
    }
}
```

### Testing Integrations

```php
<?php

use PHPUnit\Framework\TestCase;
use Papi\Core\Integrations\CustomNode;

class CustomNodeTest extends TestCase
{
    private CustomNode $node;
    
    protected function setUp(): void
    {
        $this->node = new CustomNode('test', 'Test Node');
        $this->node->setConfig(['api_key' => 'test_key']);
    }
    
    public function testNodeExecution(): void
    {
        $input = ['data' => 'test'];
        $result = $this->node->execute($input);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
    }
    
    public function testNodeExecutionWithError(): void
    {
        $this->node->setConfig(['api_key' => 'invalid_key']);
        $input = ['data' => 'test'];
        $result = $this->node->execute($input);
        
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }
}
```

### Testing AI Agents

```php
<?php

use PHPUnit\Framework\TestCase;
use Papi\Core\Agents\AIAgent;
use Papi\Core\Integrations\MockOpenAIClient;

class AIAgentTest extends TestCase
{
    private AIAgent $agent;
    private MockOpenAIClient $mockClient;
    
    protected function setUp(): void
    {
        $this->agent = new AIAgent('test', 'Test Agent');
        $this->mockClient = new MockOpenAIClient();
        $this->agent->setOpenAIClient($this->mockClient);
    }
    
    public function testAgentWithTools(): void
    {
        $tool = new CustomTool();
        $this->agent->addTool($tool);
        
        $result = $this->agent->execute(['query' => 'Use the custom tool']);
        
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function testAgentSystemPrompt(): void
    {
        $prompt = 'You are a test assistant.';
        $this->agent->setSystemPrompt($prompt);
        
        $result = $this->agent->execute(['query' => 'Hello']);
        
        $this->assertEquals('success', $result['status']);
    }
}
```

## 📋 Best Practices

### Tool Development

1. **Parameter Validation**: Always validate parameters in both `validate()` and `execute()` methods
2. **Error Handling**: Return structured error responses instead of throwing exceptions
3. **Documentation**: Provide clear descriptions and parameter documentation
4. **Testing**: Write comprehensive tests for all tool functionality
5. **Security**: Validate and sanitize all inputs, especially for database operations

### Integration Development

1. **Configuration**: Use the `config` property for static configuration
2. **Input Handling**: Accept both config and input parameters for flexibility
3. **Error Handling**: Return structured error responses with duration tracking
4. **HTTP Requests**: Use proper timeout and error handling for external API calls
5. **Logging**: Consider adding logging for debugging and monitoring

### Performance Optimization

1. **Connection Reuse**: Reuse database connections and HTTP clients when possible
2. **Caching**: Implement caching for frequently accessed data
3. **Async Operations**: Consider async operations for I/O intensive tasks
4. **Resource Cleanup**: Properly close connections and free resources
5. **Monitoring**: Track execution time and resource usage

### Security Considerations

1. **Input Validation**: Always validate and sanitize inputs
2. **Authentication**: Use secure authentication methods for external services
3. **Secrets Management**: Store API keys and secrets securely
4. **Rate Limiting**: Implement rate limiting for external API calls
5. **Error Information**: Don't expose sensitive information in error messages

## 🔧 Debugging

### Common Issues

1. **Tool Not Found**: Ensure tool names match exactly between definition and usage
2. **Parameter Validation**: Check that all required parameters are provided
3. **API Errors**: Verify API keys and endpoints are correct
4. **Network Issues**: Check connectivity and firewall settings
5. **Memory Issues**: Monitor memory usage for large data processing

### Debugging Tools

```php
<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add logging to your tools/integrations
class DebugTool implements ToolInterface
{
    public function execute(array $params): array
    {
        error_log("DebugTool executed with params: " . json_encode($params));
        
        // Your tool logic here
        
        error_log("DebugTool completed successfully");
        return ['status' => 'success', 'debug_info' => 'Tool executed'];
    }
    
    // ... other methods
}
```

### Testing Workflows

```php
<?php

// Test workflow step by step
$workflow = new Workflow('test_workflow');

// Add nodes with debug logging
$node1 = new CustomNode('node1', 'Node 1');
$node1->setConfig(['debug' => true]);

$workflow->addNode($node1);

// Execute with detailed logging
$execution = $workflow->execute(['test' => 'data']);

// Check results
foreach ($execution->getNodeResults() as $nodeId => $result) {
    echo "Node $nodeId: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
```

---

**< Previous**: [Integrations](./integrations.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Templates](./templates.md) 