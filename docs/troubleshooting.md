# Troubleshooting Guide

This guide covers common issues, debugging techniques, and solutions for Papi Core workflows.

## 🚨 Common Issues

### Workflow Execution Issues

#### Issue: "Node not found" Error
**Symptoms:**
```
Fatal error: Uncaught Error: Call to undefined method addNode()
```

**Cause:** Missing node class or incorrect namespace.

**Solution:**
```php
// Ensure proper imports
use Papi\Core\Integrations\Http\HttpNode;
use Papi\Core\Integrations\Process\ProcessNode;
use Papi\Core\Integrations\Output\EchoNode;

// Check node instantiation
$httpNode = new HttpNode('fetch', 'Fetch Data');
```

#### Issue: "Connection source/target not found" Error
**Symptoms:**
```
InvalidArgumentException: Source node 'node1' not found
```

**Cause:** Connecting to a node that doesn't exist or has a different ID.

**Solution:**
```php
// Ensure nodes are added before connections
$workflow->addNode($node1);
$workflow->addNode($node2);

// Use correct node IDs
$workflow->addConnection(new Connection('node1', 'node2'));
```

#### Issue: Workflow Validation Fails
**Symptoms:**
```
Workflow validation failed: Circular dependency detected
```

**Cause:** Circular connections between nodes.

**Solution:**
```php
// Check for circular dependencies
// Node1 -> Node2 -> Node3 -> Node1 (❌ Circular)
// Node1 -> Node2 -> Node3 (✅ Linear)

// Use workflow validation
if (!$workflow->validate()) {
    echo "Workflow validation failed\n";
}
```

### AI Agent Issues

#### Issue: "Tool not found" Error
**Symptoms:**
```
Tool 'custom_tool' not found
```

**Cause:** Tool name mismatch or tool not properly added.

**Solution:**
```php
// Ensure tool name matches exactly
class CustomTool implements ToolInterface
{
    public function getName(): string
    {
        return 'custom_tool'; // Must match exactly
    }
}

// Add tool to agent
$aiAgent->addTool(new CustomTool());
```

#### Issue: OpenAI API Schema Error
**Symptoms:**
```
Invalid schema for function 'http_request': False is not of type 'array'
```

**Cause:** Incorrect parameter schema format.

**Solution:**
```php
public function getParameters(): array
{
    return [
        'url' => [
            'type' => 'string',
            'description' => 'The URL to request',
            // Remove 'required' => true/false from individual parameters
        ],
        'method' => [
            'type' => 'string',
            'description' => 'HTTP method'
        ]
    ];
    // Use parent 'required' array instead
}
```

#### Issue: Mock Client Always Returns Mock Response
**Symptoms:** AI agent always returns mock responses even with real OpenAI client.

**Cause:** Mock client is being used instead of real client.

**Solution:**
```php
// Ensure real client is set
use Papi\Core\Integrations\RealOpenAIClient;

$realClient = new RealOpenAIClient('your-api-key');
$aiAgent->setOpenAIClient($realClient);
```

### Integration Issues

#### Issue: HTTP Request Fails
**Symptoms:**
```
cURL error: Could not resolve host
```

**Cause:** Network connectivity or URL issues.

**Solution:**
```php
// Check URL format
$httpNode->setConfig([
    'method' => 'GET',
    'url' => 'https://api.example.com/data', // Ensure valid URL
    'timeout' => 30 // Add timeout
]);

// Test connectivity
$ch = curl_init('https://api.example.com/data');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Connection error: $error\n";
}
```

#### Issue: Authentication Errors
**Symptoms:**
```
HTTP error: 401 - Unauthorized
```

**Cause:** Missing or invalid authentication headers.

**Solution:**
```php
$httpNode->setConfig([
    'method' => 'GET',
    'url' => 'https://api.example.com/data',
    'headers' => [
        'Authorization' => 'Bearer your-token',
        'Content-Type' => 'application/json'
    ]
]);
```

### Data Processing Issues

#### Issue: Data Transformation Fails
**Symptoms:**
```
Undefined index: data.title
```

**Cause:** Input data structure doesn't match expected format.

**Solution:**
```php
// Check input data structure
$input = [
    'data' => [
        'title' => 'Example Title',
        'body' => 'Example Body'
    ]
];

// Use safe data access
$processNode->setConfig([
    'operations' => [
        'extract_title' => 'data.title ?? "No title"',
        'extract_body' => 'data.body ?? "No body"'
    ]
]);
```

## 🐛 Debugging Techniques

### Enable Error Reporting

```php
<?php

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');

// Enable Papi debug mode (if available)
define('PAPI_DEBUG', true);
```

### Add Debug Logging

```php
<?php

class DebugTool implements ToolInterface
{
    public function execute(array $params): array
    {
        error_log("DebugTool: Executing with params: " . json_encode($params));
        
        try {
            $result = $this->performAction($params);
            error_log("DebugTool: Success - " . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            error_log("DebugTool: Error - " . $e->getMessage());
            throw $e;
        }
    }
}
```

### Step-by-Step Workflow Debugging

```php
<?php

// Test individual nodes
$node = new CustomNode('test', 'Test Node');
$node->setConfig(['debug' => true]);

$result = $node->execute(['test' => 'data']);
echo "Node result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Test workflow step by step
$workflow = new Workflow('debug_workflow');
$workflow->addNode($node1);
$workflow->addNode($node2);

// Test without connections first
$execution = $workflow->execute(['input' => 'test']);

// Check individual node results
foreach ($execution->getNodeResults() as $nodeId => $result) {
    echo "Node $nodeId: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
```

### Performance Debugging

```php
<?php

// Measure execution time
$startTime = microtime(true);

$execution = $workflow->execute($input);

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000;

echo "Workflow execution time: " . round($duration, 2) . "ms\n";

// Check memory usage
$memoryUsage = memory_get_peak_usage(true);
echo "Peak memory usage: " . round($memoryUsage / 1024 / 1024, 2) . "MB\n";
```

## 🔧 Testing Strategies

### Unit Testing Workflows

```php
<?php

use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    public function testWorkflowExecution(): void
    {
        $workflow = new Workflow('test_workflow');
        
        // Add test nodes
        $node = new TestNode('test', 'Test Node');
        $workflow->addNode($node);
        
        // Execute workflow
        $execution = $workflow->execute(['input' => 'test']);
        
        // Assertions
        $this->assertEquals('success', $execution->getStatus());
        $this->assertNotEmpty($execution->getOutputData());
    }
    
    public function testWorkflowValidation(): void
    {
        $workflow = new Workflow('test_workflow');
        
        // Add invalid configuration
        $node = new TestNode('test', 'Test Node');
        $node->setConfig(['invalid' => 'config']);
        $workflow->addNode($node);
        
        // Should fail validation
        $this->assertFalse($workflow->validate());
    }
}
```

### Integration Testing

```php
<?php

class IntegrationTest extends TestCase
{
    public function testHttpIntegration(): void
    {
        $httpNode = new HttpNode('test', 'Test HTTP');
        $httpNode->setConfig([
            'method' => 'GET',
            'url' => 'https://httpbin.org/get'
        ]);
        
        $result = $httpNode->execute([]);
        
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function testAIAgentIntegration(): void
    {
        $aiAgent = new AIAgent('test', 'Test Agent');
        $aiAgent->setModel('gpt-3.5-turbo')
            ->setSystemPrompt('You are a test assistant.');
        
        // Use mock client for testing
        $mockClient = new MockOpenAIClient();
        $aiAgent->setOpenAIClient($mockClient);
        
        $result = $aiAgent->execute(['query' => 'Hello']);
        
        $this->assertEquals('success', $result['status']);
    }
}
```

## 📊 Performance Optimization

### Memory Management

```php
<?php

// Clear memory after large operations
$workflow = new Workflow('large_workflow');

// Process in batches
foreach ($largeDataset as $batch) {
    $execution = $workflow->execute(['data' => $batch]);
    
    // Clear memory
    unset($execution);
    gc_collect_cycles();
}
```

### Connection Reuse

```php
<?php

class OptimizedHttpNode extends Node
{
    private static $httpClient = null;
    
    private function getHttpClient()
    {
        if (self::$httpClient === null) {
            self::$httpClient = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10
            ]);
        }
        return self::$httpClient;
    }
}
```

### Caching

```php
<?php

class CachedTool implements ToolInterface
{
    private array $cache = [];
    
    public function execute(array $params): array
    {
        $cacheKey = md5(json_encode($params));
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $result = $this->performExpensiveOperation($params);
        $this->cache[$cacheKey] = $result;
        
        return $result;
    }
}
```

## 🔒 Security Considerations

### Input Validation

```php
<?php

class SecureTool implements ToolInterface
{
    public function execute(array $params): array
    {
        // Validate and sanitize inputs
        $url = filter_var($params['url'] ?? '', FILTER_VALIDATE_URL);
        if (!$url) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }
        
        // Sanitize other inputs
        $message = htmlspecialchars($params['message'] ?? '', ENT_QUOTES, 'UTF-8');
        
        return $this->processSecureData($url, $message);
    }
}
```

### API Key Management

```php
<?php

// Use environment variables for sensitive data
$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
if (empty($apiKey)) {
    throw new \RuntimeException('OpenAI API key not configured');
}

$aiAgent->setOpenAIClient(new RealOpenAIClient($apiKey));
```

## 📞 Getting Help

### Before Asking for Help

1. **Check the logs**: Look for error messages and stack traces
2. **Reproduce the issue**: Create a minimal example that reproduces the problem
3. **Check documentation**: Review relevant documentation sections
4. **Search existing issues**: Check if the issue has been reported before

### Providing Useful Information

When reporting issues, include:

```php
// Environment information
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Papi Core Version: " . PapiCore::VERSION . "\n";
echo "OS: " . PHP_OS . "\n";

// Workflow configuration
echo "Workflow: " . json_encode($workflow->toArray(), JSON_PRETTY_PRINT) . "\n";

// Error details
echo "Error: " . $e->getMessage() . "\n";
echo "Stack trace: " . $e->getTraceAsString() . "\n";
```

### Community Resources

- **Documentation**: [Getting Started](./getting-started.md), [API Reference](./api-reference.md)
- **Issues**: [GitHub Issues](https://github.com/papi-ai/papi-core/issues)
- **Discussions**: [GitHub Discussions](https://github.com/papi-ai/papi-core/discussions)
- **Examples**: Check the [examples directory](../examples/) for working code

## 🔗 Related Documentation

- [Getting Started](./getting-started.md) - Basic setup and usage
- [Developer Guide](./developer-guide.md) - Advanced development topics
- [API Reference](./api-reference.md) - Complete API documentation
- [Workflow Patterns](./workflow-patterns.md) - Common patterns and best practices

---

**< Previous**: [API Reference](./api-reference.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Documentation Index](./index.md) 