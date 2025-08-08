# Workflow Design Patterns

Papi supports a variety of workflow patterns to automate complex processes. Here are the common patterns and their implementation status:

## 1. Sequential Workflow ✅ **Implemented**
Nodes are executed one after another in a linear sequence.

```php
use Papi\Core\Workflow;
use Papi\Core\Connection;

$workflow = new Workflow('Sequential Workflow');

// Add nodes
$workflow->addNode($node1);
$workflow->addNode($node2);
$workflow->addNode($node3);

// Connect sequentially
$workflow->addConnection(new Connection('node1', 'node2'));
$workflow->addConnection(new Connection('node2', 'node3'));

// Execute
$execution = $workflow->execute(['input' => 'data']);
```

## 2. Parallel Workflow 🔄 **Planned**
Multiple nodes will be executed simultaneously for improved performance.

```php
// Future implementation
// Node1
//   |\
//   | \
// Node2 Node3
//   |   |
//   |   |
// Node4 Node5
```

## 3. Conditional Workflow 🔄 **Planned**
Nodes will be executed based on conditions and data flow.

```php
// Future implementation
// if (condition) NodeA else NodeB
```

## 4. Loop Workflow 🔄 **Planned**
A node or set of nodes will be executed repeatedly for a list of items.

```php
// Future implementation
// foreach (item in list) { Node }
```

## Current Implementation Examples

### Data Fetch and Process Pattern
```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;
use Papi\Core\Integrations\MockOpenAIClient;

$workflow = new Workflow('Data Processing Workflow');

// Create AI agent for data processing
$aiAgent = new AIAgent('assistant', 'Data Processor');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a data processing assistant that can analyze and transform data.');

// Use mock client for testing
$mockClient = new MockOpenAIClient([
    'Process this data' => 'I have processed the data successfully.'
]);
$aiAgent->setLLMClient($mockClient);

// Create output node
$outputNode = new Output('output', 'Output Results', [
    'format' => 'json',
    'pretty_print' => true
]);

// Add nodes and connections
$workflow->addNode($aiAgent);
$workflow->addNode($outputNode);

$workflow->addConnection(new Connection('assistant', 'output'));

// Execute
$execution = $workflow->execute([
    'query' => 'Process this data'
]);
```

### AI Agent with Custom Tools Pattern
```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;

// Create a custom calculation tool
class CalculationTool implements Node, Tool
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
        $operation = $input['operation'] ?? '';
        $numbers = $input['numbers'] ?? [];
        
        switch ($operation) {
            case 'add':
                $result = array_sum($numbers);
                break;
            case 'multiply':
                $result = array_product($numbers);
                break;
            case 'sqrt':
                $result = sqrt($numbers[0] ?? 0);
                break;
            default:
                $result = 0;
        }
        
        return ['result' => $result];
    }
    
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function toArray(): array { return ['id' => $this->id, 'name' => $this->name]; }
    
    public function getToolSchema(): array
    {
        return [
            'name' => 'calculation',
            'description' => 'Perform mathematical calculations',
            'parameters' => [
                'operation' => ['type' => 'string', 'enum' => ['add', 'multiply', 'sqrt']],
                'numbers' => ['type' => 'array', 'items' => ['type' => 'number']]
            ]
        ];
    }
    
    public function getToolName(): string { return 'calculation'; }
    public function getToolDescription(): string { return 'Perform mathematical calculations'; }
}

$workflow = new Workflow('AI Agent Workflow');

// Create AI agent with custom tool
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can perform calculations.')
    ->addTool(new CalculationTool('calc', 'Calculator'));

$workflow->addNode($aiAgent);

// Execute with query
$execution = $workflow->execute([
    'query' => 'What is the square root of 144?'
]);
```

## Best Practices

### Node Design
- **Single Responsibility**: Keep nodes focused on one specific task
- **Clear Naming**: Use descriptive names for nodes and connections
- **Error Handling**: Implement proper error handling in custom nodes
- **Configuration**: Use the `setConfig()` method for static configuration

### Workflow Design
- **Validation**: Always validate workflows before execution
- **Error Recovery**: Handle errors gracefully and provide meaningful feedback
- **Performance**: Consider execution time and resource usage
- **Testing**: Test workflows with various input scenarios

### Data Flow
- **Input Validation**: Validate input data at the workflow level
- **Data Transformation**: Use connections to transform data between nodes
- **Output Formatting**: Ensure consistent output formats across nodes

## Performance Considerations

### Current Limitations
- **Sequential Execution**: All nodes execute sequentially (parallel execution planned)
- **Memory Usage**: Large datasets may require optimization
- **Network Calls**: External API calls can impact performance

### Optimization Tips
- **Connection Reuse**: Reuse HTTP connections when possible
- **Caching**: Implement caching for frequently accessed data
- **Batch Processing**: Process data in batches for large datasets
- **Error Handling**: Implement proper error handling to avoid workflow failures

## Related Documentation
- [Getting Started](./getting-started.md) - Basic workflow setup
- [AI Agents](./ai-agents.md) - Working with AI agents
- [Integrations](./integrations.md) - Available integrations
- [Developer Guide](./developer-guide.md) - Creating custom nodes and tools

---

**< Previous**: [Getting Started](./getting-started.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [AI Agents](./ai-agents.md) 