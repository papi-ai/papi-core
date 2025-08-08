# Workflow Templates

Papi provides patterns and examples for creating reusable workflow templates. While a formal template system is planned for future releases, you can create reusable workflow patterns using code.

## 🎯 Common Workflow Patterns

### Customer Support Automation

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Agents\AIAgent;
use Papi\Core\Tools\HttpTool;
use Papi\Core\Integrations\Http\HttpNode;

function createCustomerSupportWorkflow(string $slackWebhook, string $aiModel = 'gpt-3.5-turbo'): Workflow
{
    $workflow = new Workflow('customer_support_automation');
    
    // Create AI agent for support
    $aiAgent = new AIAgent('support_agent', 'Customer Support Agent');
    $aiAgent->setModel($aiModel)
        ->setSystemPrompt('You are a helpful customer support agent. Be polite, professional, and solve customer issues efficiently.')
        ->addTool(new HttpTool());
    
    // Create Slack notification node
    $slackNode = new SlackNode('notify', 'Send to Slack');
    $slackNode->setConfig(['webhook_url' => $slackWebhook]);
    
    // Add nodes and connections
    $workflow->addNode($aiAgent);
    $workflow->addNode($slackNode);
    $workflow->addConnection(new Connection('support_agent', 'notify'));
    
    return $workflow;
}

// Usage
$workflow = createCustomerSupportWorkflow('https://hooks.slack.com/...', 'gpt-4');
$execution = $workflow->execute(['customer_message' => 'I need help with my order']);
```

### Data Processing Workflow

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;

function createDataProcessingWorkflow(string $systemPrompt): Workflow
{
    $workflow = new Workflow('data_processing_workflow');
    
    // AI agent to process data
    $aiAgent = new AIAgent('processor', 'Data Processor');
    $aiAgent->setModel('gpt-3.5-turbo')
        ->setSystemPrompt($systemPrompt);
    
    // Output node to format results
    $outputNode = new Output('output', 'Output Results', [
        'format' => 'json',
        'pretty_print' => true
    ]);
    
    // Add nodes and connections
    $workflow->addNode($aiAgent);
    $workflow->addNode($processNode);
    $workflow->addNode($outputNode);
    
    $workflow->addConnection(new Connection('fetch', 'process'));
    $workflow->addConnection(new Connection('process', 'output'));
    
    return $workflow;
}

// Usage
$transformations = [
    'extract_title' => 'data.title',
    'extract_body' => 'data.body',
    'format_date' => 'data.created_at'
];

$workflow = createDataProcessingWorkflow('https://api.example.com/posts', $transformations);
$execution = $workflow->execute();
```

### AI Analysis Workflow

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;

function createAIAnalysisWorkflow(string $systemPrompt, array $tools = []): Workflow
{
    $workflow = new Workflow('ai_analysis_workflow');
    
    // Create AI agent
    $aiAgent = new AIAgent('analyst', 'AI Analyst');
    $aiAgent->setModel('gpt-4')
        ->setSystemPrompt($systemPrompt);
    
    // Add custom tools
    foreach ($tools as $tool) {
        $aiAgent->addTool($tool);
    }
    
    // Output node
    $outputNode = new Output('output', 'Analysis Results', [
        'format' => 'json',
        'pretty_print' => true,
        'include_metadata' => true
    ]);
    
    // Add nodes and connections
    $workflow->addNode($aiAgent);
    $workflow->addNode($outputNode);
    $workflow->addConnection(new Connection('analyst', 'output'));
    
    return $workflow;
}

// Usage
$systemPrompt = 'You are a data analyst. Analyze the provided data and provide insights with recommendations.';
$workflow = createAIAnalysisWorkflow($systemPrompt);
$execution = $workflow->execute(['query' => 'Analyze this sales data']);
```

## 🔧 Creating Reusable Workflow Functions

### Base Workflow Builder

```php
<?php

abstract class BaseWorkflowBuilder
{
    protected Workflow $workflow;
    protected array $config;
    
    public function __construct(string $name, array $config = [])
    {
        $this->workflow = new Workflow($name);
        $this->config = $config;
    }
    
    abstract public function build(): Workflow;
    
    protected function addNode(Node $node): self
    {
        $this->workflow->addNode($node);
        return $this;
    }
    
    protected function addConnection(string $from, string $to): self
    {
        $this->workflow->addConnection(new Connection($from, $to));
        return $this;
    }
    
    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }
}
```

### Specific Workflow Builders

```php
<?php

class NotificationWorkflowBuilder extends BaseWorkflowBuilder
{
    public function build(): Workflow
    {
        $trigger = $this->createTriggerNode();
        $processor = $this->createProcessorNode();
        $notifier = $this->createNotifierNode();
        
        $this->addNode($trigger)
             ->addNode($processor)
             ->addNode($notifier)
             ->addConnection('trigger', 'processor')
             ->addConnection('processor', 'notifier');
        
        return $this->workflow;
    }
    
    private function createTriggerNode(): Node
    {
        // Implementation depends on trigger type
        return new HttpNode('trigger', 'Webhook Trigger');
    }
    
    private function createProcessorNode(): Node
    {
        return new ProcessNode('processor', 'Process Data');
    }
    
    private function createNotifierNode(): Node
    {
        $notifier = new SlackNode('notifier', 'Send Notification');
        $notifier->setConfig(['webhook_url' => $this->config['slack_webhook']]);
        return $notifier;
    }
}

// Usage
$builder = new NotificationWorkflowBuilder('notification_workflow', [
    'slack_webhook' => 'https://hooks.slack.com/...'
]);
$workflow = $builder->build();
```

## 📋 Workflow Configuration

### Configuration Schema

```php
<?php

class WorkflowConfig
{
    public function __construct(
        public readonly string $name,
        public readonly array $nodes = [],
        public readonly array $connections = [],
        public readonly array $settings = []
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['nodes'] ?? [],
            $data['connections'] ?? [],
            $data['settings'] ?? []
        );
    }
    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'nodes' => $this->nodes,
            'connections' => $this->connections,
            'settings' => $this->settings
        ];
    }
}
```

### Workflow Factory

```php
<?php

class WorkflowFactory
{
    private array $templates = [];
    
    public function registerTemplate(string $name, callable $builder): self
    {
        $this->templates[$name] = $builder;
        return $this;
    }
    
    public function create(string $templateName, array $config = []): Workflow
    {
        if (!isset($this->templates[$templateName])) {
            throw new \InvalidArgumentException("Template '$templateName' not found");
        }
        
        $builder = $this->templates[$templateName];
        return $builder($config);
    }
    
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
}

// Usage
$factory = new WorkflowFactory();

$factory->registerTemplate('customer_support', function(array $config) {
    return createCustomerSupportWorkflow($config['slack_webhook'], $config['ai_model'] ?? 'gpt-3.5-turbo');
});

$factory->registerTemplate('data_processing', function(array $config) {
    return createDataProcessingWorkflow($config['api_url'], $config['transformations'] ?? []);
});

// Create workflows from templates
$supportWorkflow = $factory->create('customer_support', [
    'slack_webhook' => 'https://hooks.slack.com/...',
    'ai_model' => 'gpt-4'
]);

$dataWorkflow = $factory->create('data_processing', [
    'api_url' => 'https://api.example.com/data',
    'transformations' => ['extract_title' => 'data.title']
]);
```

## 🔄 Planned Template System

### Future Features
- **Template Registry**: Centralized template management
- **Template Sharing**: Community template sharing platform
- **Template Validation**: Automatic template validation and testing
- **Template Versioning**: Version control for templates
- **Template Marketplace**: Browse and install community templates

### Template Format (Planned)
```json
{
  "name": "customer_support_automation",
  "version": "1.0.0",
  "description": "Automated customer support workflow",
  "author": "papi-ai",
  "nodes": [
    {
      "id": "support_agent",
      "type": "ai_agent",
      "config": {
        "model": "gpt-3.5-turbo",
        "system_prompt": "You are a helpful customer support agent."
      }
    }
  ],
  "connections": [
    {
      "from": "support_agent",
      "to": "slack_notification"
    }
  ],
  "parameters": [
    {
      "name": "slack_webhook",
      "type": "string",
      "required": true,
      "description": "Slack webhook URL"
    }
  ]
}
```

## 🔗 Related Documentation

- [Getting Started](./getting-started.md) - Basic workflow setup
- [Workflow Patterns](./workflow-patterns.md) - Common workflow patterns
- [AI Agents](./ai-agents.md) - Working with AI agents
- [Integrations](./integrations.md) - Available integrations
- [Developer Guide](./developer-guide.md) - Creating custom workflows

---

**< Previous**: [Developer Guide](./developer-guide.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [API Reference](./api-reference.md) 