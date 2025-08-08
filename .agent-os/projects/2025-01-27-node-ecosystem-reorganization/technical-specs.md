# Node Ecosystem Reorganization - Technical Specifications

## Architecture Overview

### Target Directory Structure

```
src/
├── Nodes/
│   ├── AI/
│   │   ├── AIAgent.php
│   │   ├── LLM.php
│   │   ├── Memory/
│   │   │   ├── MemoryInterface.php
│   │   │   └── InMemoryMemory.php
│   │   └── Trigger/
│   │       ├── TriggerInterface.php
│   │       ├── BaseTriggerNode.php
│   │       ├── ChatTriggerNode.php
│   │       ├── EmailTriggerNode.php
│   │       └── ManualTriggerNode.php
│   ├── Integration/
│   │   ├── Google/
│   │   │   ├── Sheets.php
│   │   │   ├── Gmail.php
│   │   │   ├── Drive.php
│   │   │   └── Calendar.php
│   │   ├── Slack/
│   │   │   ├── ChatMessage.php
│   │   │   ├── Channel.php
│   │   │   └── Webhook.php
│   │   ├── Database/
│   │   │   ├── MySQL.php
│   │   │   ├── PostgreSQL.php
│   │   │   └── MongoDB.php
│   │   ├── FileSystem/
│   │   │   ├── Local.php
│   │   │   ├── S3.php
│   │   │   └── GoogleDrive.php
│   │   └── Communication/
│   │       ├── Email.php
│   │       ├── SMS.php
│   │       └── WhatsApp.php
│   ├── Utility/
│   │   ├── Http.php
│   │   ├── Process.php
│   │   └── Output.php
│   └── Core/
│       ├── Node.php
│       ├── Workflow.php
│       ├── Connection.php
│       └── Execution.php
├── Tools/
│   ├── HttpTool.php
│   ├── MathTool.php
│   └── ToolInterface.php
└── Integrations/
    ├── OpenAIClient.php
    ├── RealOpenAIClient.php
    └── MockOpenAIClient.php
```

### Namespace Organization

```php
// AI Nodes
namespace Papi\Core\Nodes\AI;
namespace Papi\Core\Nodes\AI\Memory;
namespace Papi\Core\Nodes\AI\Trigger;

// Integration Nodes
namespace Papi\Core\Nodes\Integration\Google;
namespace Papi\Core\Nodes\Integration\Slack;
namespace Papi\Core\Nodes\Integration\Database;
namespace Papi\Core\Nodes\Integration\FileSystem;
namespace Papi\Core\Nodes\Integration\Communication;

// Utility Nodes
namespace Papi\Core\Nodes\Utility;

// Core Components
namespace Papi\Core\Nodes\Core;
```

## Node Metadata System

### NodeMetadata Interface

```php
<?php

namespace Papi\Core\Nodes;

interface NodeMetadata
{
    public function getName(): string;
    public function getDescription(): string;
    public function getCategory(): string;
    public function getVersion(): string;
    public function getAuthor(): string;
    public function getIcon(): string;
    public function getInputSchema(): array;
    public function getOutputSchema(): array;
    public function getConfigurationSchema(): array;
    public function getExamples(): array;
    public function getDependencies(): array;
    public function isDeprecated(): bool;
}
```

### Node Registry System

```php
<?php

namespace Papi\Core\Nodes;

class NodeRegistry
{
    private array $nodes = [];
    
    public function register(string $className, NodeMetadata $metadata): void;
    public function getNode(string $name): ?NodeMetadata;
    public function getNodesByCategory(string $category): array;
    public function getAllNodes(): array;
    public function discoverNodes(): void;
}
```

## Migration Strategy

### Phase 1: Create New Structure
1. Create new directory structure
2. Create NodeMetadata interface and NodeRegistry
3. Update composer autoloader configuration
4. Create migration scripts

### Phase 2: Move AI Nodes
1. Move AIAgent to `/src/Nodes/AI/AIAgent.php`
2. Move LLMNode to `/src/Nodes/AI/LLM.php`
3. Move Memory classes to `/src/Nodes/AI/Memory/`
4. Move Trigger classes to `/src/Nodes/AI/Trigger/`
5. Update all namespace declarations
6. Update all import statements

### Phase 3: Move Integration Nodes
1. Move HTTP node to `/src/Nodes/Utility/Http.php`
2. Move Process node to `/src/Nodes/Utility/Process.php`
3. Move Output node to `/src/Nodes/Utility/Output.php`
4. Create placeholder directories for future integrations
5. Update all references

### Phase 4: Move Core Components
1. Move Node.php to `/src/Nodes/Core/Node.php`
2. Move Workflow.php to `/src/Nodes/Core/Workflow.php`
3. Move Connection.php to `/src/Nodes/Core/Connection.php`
4. Move Execution.php to `/src/Nodes/Core/Execution.php`
5. Update all references

### Phase 5: Add Metadata
1. Add NodeMetadata to all existing nodes
2. Create node documentation
3. Update README with new structure
4. Create node discovery examples

## Implementation Details

### Composer Autoloader Configuration

```json
{
    "autoload": {
        "psr-4": {
            "Papi\\Core\\": "src/",
            "Papi\\Core\\Nodes\\": "src/Nodes/",
            "Papi\\Core\\Nodes\\AI\\": "src/Nodes/AI/",
            "Papi\\Core\\Nodes\\Integration\\": "src/Nodes/Integration/",
            "Papi\\Core\\Nodes\\Utility\\": "src/Nodes/Utility/",
            "Papi\\Core\\Nodes\\Core\\": "src/Nodes/Core/"
        }
    }
}
```

### Node Base Classes

```php
<?php

namespace Papi\Core\Nodes\Core;

abstract class Node
{
    protected NodeMetadata $metadata;
    
    public function getMetadata(): NodeMetadata
    {
        return $this->metadata;
    }
    
    abstract public function execute(array $input): array;
}
```

### Integration Node Base Class

```php
<?php

namespace Papi\Core\Nodes\Integration;

abstract class IntegrationNode extends \Papi\Core\Nodes\Core\Node
{
    protected array $config = [];
    
    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }
    
    protected function validateConfig(): bool
    {
        return true;
    }
}
```

## Testing Strategy

### Test Organization

```
tests/
├── Unit/
│   ├── Nodes/
│   │   ├── AI/
│   │   │   ├── AIAgentTest.php
│   │   │   ├── LLMTest.php
│   │   │   ├── Memory/
│   │   │   └── Trigger/
│   │   ├── Integration/
│   │   │   ├── Google/
│   │   │   ├── Slack/
│   │   │   └── Database/
│   │   ├── Utility/
│   │   └── Core/
│   └── Tools/
└── Integration/
    └── Nodes/
```

### Test Migration Strategy
1. Update test namespaces to match new structure
2. Update test imports and references
3. Ensure all tests pass after migration
4. Add tests for new metadata system

## Documentation Updates

### README Updates
1. Update node usage examples with new namespaces
2. Add node discovery documentation
3. Update directory structure documentation
4. Add migration guide for existing users

### API Documentation
1. Document new node structure
2. Add node metadata examples
3. Create node discovery examples
4. Update integration examples

## Performance Considerations

### Autoloading Optimization
- Use PSR-4 autoloading for optimal performance
- Minimize file system operations during node discovery
- Cache node metadata when possible

### Memory Management
- Lazy load node metadata when needed
- Use efficient data structures for node registry
- Minimize memory footprint of metadata objects

## Backward Compatibility

### Deprecation Strategy
1. Keep old namespaces working with deprecation warnings
2. Provide migration guide for existing code
3. Support both old and new structures during transition
4. Remove old structure after sufficient migration time

### Migration Tools
1. Create automated migration script
2. Provide namespace update tool
3. Create compatibility layer if needed
4. Document migration process

## Future Extensibility

### Plugin System Preparation
- Design node structure to support plugins
- Create interfaces for plugin developers
- Plan for external node registration
- Design version compatibility system

### Service Provider Integration
- Plan for Laravel service provider integration
- Design for Symfony bundle compatibility
- Create framework-agnostic node loading
- Plan for dependency injection integration 