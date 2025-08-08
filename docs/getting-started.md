# Getting Started with Papi

Welcome to **Papi** – the PHP AI Agents Workflow Automation Library!

## Installation

Papi requires PHP 8.1+ and Composer. To install:

```bash
composer require papi/papi-core
```

## Basic Usage Example

Here's how to create and run a simple workflow:

```php
<?php

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;
use Papi\Core\Integrations\MockOpenAIClient;

$workflow = new Workflow('Demo Workflow');

// Create AI agent for data processing
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can analyze and summarize data.');

// Use mock client for testing
$mockClient = new MockOpenAIClient([
    'Analyze this data' => 'I have analyzed the data and found it contains important information about user preferences.'
]);
$aiAgent->setLLMClient($mockClient);

// Create output node
$outputNode = new Output('output', 'Output Results', [
    'format' => 'json',
    'pretty_print' => true
]);

$workflow->addNode($aiAgent);
$workflow->addNode($outputNode);
$workflow->addConnection(new Connection('assistant', 'output'));

$execution = $workflow->execute([
    'query' => 'Analyze this data'
]);
print_r($execution->getOutputData());
```

## Next Steps
- Explore [Workflow Patterns](./workflow-patterns.md)
- Learn about [AI Agents](./ai-agents.md)
- See available [Integrations](./integrations.md)
- Browse the [API Reference](./api-reference.md)
- Try [Templates](./templates.md)
- Check the [Developer Guide](./developer-guide.md) for advanced topics
- Troubleshoot issues with the [Troubleshooting Guide](./troubleshooting.md)

---

**< Previous**: [Documentation Index](./index.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Workflow Patterns](./workflow-patterns.md) 