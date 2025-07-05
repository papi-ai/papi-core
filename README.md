# papi-core

**papi-core** is the decoupled PHP library powering [papi-ai](https://github.com/papi-ai), an open-source, n8n-inspired AI workflow automation platform.

- **Modern workflow engine**: Compose, execute, and extend workflows with AI agents, integrations, and custom nodes.
- **Extensible**: Add new tools, integrations, and agents via PHP classes or plugins.
- **Framework-agnostic**: Use standalone or with Laravel/Symfony bundles.
- **Part of the [papi-ai](https://github.com/papi-ai) ecosystem**: See also [papi-ui](https://github.com/papi-ai/papi-ui), [papi-symfony-bundle](https://github.com/papi-ai/papi-symfony-bundle), [papi-plugins](https://github.com/papi-ai/papi-plugins), and [papi-website](https://github.com/papi-ai/papi-website).

## Features
- Workflow engine (nodes, connections, execution)
- AI agent support (tool-calling, OpenAI integration)
- Extensible tool/integration system
- Plugin discovery (coming soon)

## Installation

```
composer require papi-ai/papi-core
```

## Basic Usage

```php
use Papi\Core\Workflow;
use Papi\Core\Agents\AIAgent;
// ...

$workflow = new Workflow(/* ... */);
$result = $workflow->execute([/* input data */]);
```

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md) or open an issue/PR.

## License
MIT 