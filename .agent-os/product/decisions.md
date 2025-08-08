# Papi Core Technical Decisions

## Architecture Decisions

### Decoupled Library Design
**Decision**: Build Papi Core as a standalone PHP library rather than a framework-specific package.

**Rationale**: 
- Enables adoption across different PHP frameworks and standalone applications
- Reduces coupling and allows for framework-specific bundles to be built on top
- Follows the Unix philosophy of "do one thing well"

**Alternatives Considered**: 
- Framework-specific packages (Laravel-only, Symfony-only)
- Monolithic application with built-in UI

### Node-Based Architecture
**Decision**: Use a node-based system where each processing unit is a separate node class.

**Rationale**:
- Provides clear separation of concerns
- Enables easy extensibility through custom nodes
- Allows for visual workflow representation
- Follows established patterns from tools like n8n and Node-RED

**Implementation**: Each node extends the base `Node` class and implements `execute()` method.

### Connection-Based Data Flow
**Decision**: Use explicit connections between nodes rather than implicit data passing.

**Rationale**:
- Makes data flow explicit and traceable
- Enables visual workflow representation
- Allows for data transformation between nodes
- Supports conditional routing and branching

## Technology Stack Decisions

### PHP 8.1+ Requirement
**Decision**: Require PHP 8.1 or higher as minimum version.

**Rationale**:
- Access to modern PHP features (typed properties, match expressions, etc.)
- Better performance and memory management
- Enhanced type safety and error handling
- Aligns with current PHP ecosystem standards

### PSR-12 Code Style
**Decision**: Enforce PSR-12 coding standards.

**Rationale**:
- Industry standard for PHP code formatting
- Improves code readability and maintainability
- Enables easy integration with existing PHP projects
- Consistent with modern PHP development practices

### PHPStan Level 8
**Decision**: Use PHPStan at maximum level (8) for static analysis.

**Rationale**:
- Catches potential bugs and type issues early
- Enforces strict typing throughout the codebase
- Improves code quality and maintainability
- Provides confidence in refactoring and changes

## AI Integration Decisions

### OpenAI as Primary Provider
**Decision**: Start with OpenAI as the primary AI provider.

**Rationale**:
- Most mature and widely adopted API
- Excellent tool-calling capabilities
- Comprehensive documentation and community support
- Stable and reliable service

**Future**: Plan to add support for Anthropic Claude and other providers.

### Tool-Calling Pattern
**Decision**: Implement tool-calling pattern for AI agent capabilities.

**Rationale**:
- Enables AI agents to perform specific actions
- Provides structured way to extend AI capabilities
- Follows OpenAI's function calling standards
- Allows for easy testing and mocking

### Simple LLM Node
**Decision**: Create a separate LLM node for basic text generation without tool-calling capabilities.

**Rationale**:
- Provides simpler interface for basic text generation tasks
- Reduces complexity when tools are not needed
- Enables more focused use cases (summarization, translation, etc.)
- Allows for different configuration and optimization strategies

### AI Agent Memory System
**Decision**: Implement memory/context management for AI agents to maintain conversation history.

**Rationale**:
- Enables multi-turn conversations with context preservation
- Improves AI agent responses by maintaining conversation history
- Supports complex workflows requiring conversation state
- Allows for better user experience in interactive scenarios

### Trigger System
**Decision**: Implement a trigger system to initiate workflows from external events.

**Rationale**:
- Enables event-driven workflow execution
- Supports real-time integration with external systems
- Allows for automated workflow initiation
- Provides flexibility in workflow entry points

**Implementation**: 
- Trigger nodes can only be used at workflow start
- No input required (trigger nodes are entry points)
- Configurable to listen to different trigger types (chat, email, manual, etc.)
- Outputs trigger data to connected nodes

### Interface-Based Node System
**Decision**: Implement a clean interface-based system where nodes can implement multiple capabilities through interfaces.

**Rationale**:
- Provides type safety and clear capability contracts
- Allows nodes to have multiple capabilities (e.g., both Tool and Memory)
- Follows PHP best practices and SOLID principles
- Simpler and more extensible than complex capability systems

**Implementation**:
- **Core Interfaces**: `Node`, `Tool`, `Memory`, `Trigger`
- **Type-Safe Capabilities**: Interface-based capability checking
- **Multi-Capability Nodes**: Nodes can implement multiple interfaces
- **Clean Architecture**: Each interface has a single responsibility

**Example Implementation**:
```php
class GoogleSheets implements Node, Tool, Memory
{
    // Node interface
    public function execute(array $input): array { /* ... */ }
    
    // Tool interface
    public function getToolSchema(): array { /* ... */ }
    
    // Memory interface
    public function addMessage(string $role, string $content): void { /* ... */ }
}

// Type-safe usage
$aiAgent->addTool($sheetsNode);     // Only Tool nodes
$aiAgent->setMemory($sheetsNode);   // Only Memory nodes
```

**Benefits**:
- **Type Safety**: Compile-time checking of capabilities
- **Flexibility**: Nodes can have multiple capabilities
- **Extensibility**: Easy to add new interfaces
- **Clean Design**: Follows PHP interface patterns

### Node Ecosystem Reorganization
**Decision**: Reorganize node structure to improve discoverability and follow n8n patterns.

**Rationale**:
- Improves developer experience and node discoverability
- Follows proven patterns from successful workflow automation tools
- Enables better organization as the node ecosystem grows
- Supports future plugin system and external node registration

**Implementation**:
- Create unified `/src/Nodes/` directory structure
- Organize nodes by category (AI, Integration, Utility, Core)
- Group integration nodes by service provider (Google, Slack, etc.)
- Implement NodeMetadata system for documentation and discovery
- Add NodeRegistry for automatic node discovery and categorization

**Target Structure**:
```
src/Nodes/
├── AI/           # AIAgent, LLM, Memory, Trigger
├── Integration/  # Service-specific integrations
│   ├── Google/   # Sheets, Gmail, Drive, Calendar
│   ├── Slack/    # Chat, Channels, Webhooks
│   └── Database/ # MySQL, PostgreSQL, MongoDB
├── Utility/      # Memory, Output
└── Core/         # Node, Workflow, Connection, Execution
```

### Mock Client for Testing
**Decision**: Include a mock OpenAI client for testing.

**Rationale**:
- Enables reliable testing without API costs
- Allows for testing different scenarios and edge cases
- Speeds up test execution
- Provides consistent test environment

## Testing Strategy

### PHPUnit as Testing Framework
**Decision**: Use PHPUnit for unit and integration testing.

**Rationale**:
- Industry standard for PHP testing
- Excellent integration with PHP ecosystem
- Rich feature set for mocking and assertions
- Good IDE support and debugging capabilities

### Comprehensive Test Coverage
**Decision**: Aim for high test coverage (90%+) across the codebase.

**Rationale**:
- Ensures code quality and reliability
- Enables confident refactoring and changes
- Provides documentation through tests
- Reduces regression bugs

## Package Management

### Composer for Dependency Management
**Decision**: Use Composer for package management and autoloading.

**Rationale**:
- Standard for PHP package management
- Excellent dependency resolution
- PSR-4 autoloading support
- Wide ecosystem integration

### MIT License
**Decision**: Use MIT license for the project.

**Rationale**:
- Permissive and business-friendly
- Encourages adoption and contribution
- Compatible with most other licenses
- Simple and well-understood terms

## Future Considerations

### Plugin System Architecture
**Consideration**: Design plugin system for extensibility.

**Approach**: 
- Plugin discovery and loading mechanism
- Standardized plugin interface
- Version compatibility management
- Marketplace infrastructure

### Performance Optimization
**Consideration**: Optimize for high-performance workflow execution.

**Approach**:
- Async/await patterns where appropriate
- Connection pooling for external services
- Caching strategies for repeated operations
- Memory management for large workflows

### Security Considerations
**Consideration**: Implement security best practices.

**Approach**:
- Input validation and sanitization
- Secure handling of API keys and credentials
- Rate limiting and abuse prevention
- Audit logging for sensitive operations 