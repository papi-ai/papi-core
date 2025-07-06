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
use Papi\Core\Workflow;
use Papi\Integrations\Http\HttpNode;
use Papi\Integrations\Process\ProcessNode;
use Papi\Integrations\Output\EchoNode;

$workflow = new Workflow('Demo Workflow');

$httpNode = new HttpNode('fetch', 'Fetch Data');
$httpNode->setConfig([
    'method' => 'GET',
    'url' => 'https://jsonplaceholder.typicode.com/posts/1',
]);

$processNode = new ProcessNode('process', 'Process Data');
$processNode->setConfig([
    'operations' => [
        'extract_title' => 'data.title',
        'extract_body' => 'data.body',
    ]
]);

$echoNode = new EchoNode('output', 'Output');
$echoNode->setConfig([
    'format' => 'json',
    'pretty_print' => true
]);

$workflow->addNode($httpNode);
$workflow->addNode($processNode);
$workflow->addNode($echoNode);
$workflow->addConnection(new \Papi\Core\Connection('fetch', 'process'));
$workflow->addConnection(new \Papi\Core\Connection('process', 'output'));

$execution = $workflow->execute();
print_r($execution->getOutput());
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