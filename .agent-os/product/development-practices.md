# Papi Core Development Practices

## Test-Driven Development (TDD) Flow

### TDD Cycle: Red-Green-Refactor

1. **Red**: Write a failing test that defines the desired behavior
2. **Green**: Write the minimum code to make the test pass
3. **Refactor**: Clean up the code while keeping tests green

### Test-First Philosophy

- **Always write tests first** before implementing any feature
- **Test the interface, not the implementation**
- **One assertion per test** for clarity and isolation
- **Use descriptive test names** that explain the expected behavior

### Testing Strategy

#### Unit Tests
- **Target**: 90%+ code coverage
- **Scope**: Test individual classes and methods in isolation
- **Mocking**: Use Prophecy for test doubles
- **Location**: `tests/Unit/` directory

#### Integration Tests
- **Target**: Test interactions between components
- **Scope**: Test workflow execution, node connections, data flow
- **Location**: `tests/Integration/` directory

#### Feature Tests
- **Target**: Test complete features end-to-end
- **Scope**: Test full workflow scenarios with real data
- **Location**: `tests/Feature/` directory

### Test-Driven Development (TDD) Cycle

Follow the Red-Green-Refactor cycle:

1. **Red**: Write ONE failing test that describes the desired behavior
2. **Green**: Write the minimum code necessary to make the test pass
3. **Refactor**: Refactor the code to improve design while keeping tests green
4. **Repeat**: Write the next test and repeat the cycle

#### TDD Guidelines
- Write tests before writing implementation code
- Write ONE test at a time - never multiple tests
- Write small, focused tests that are easy to understand
- Ensure tests describe behavior, not implementation details
- Refactor frequently to maintain clean, maintainable code
- Only move to the next test after the current one passes

### Testing Smells to Avoid

#### Code Smells
- **Long test methods**: Keep tests short and focused on single behavior
- **Complex test setup**: Indicates tight coupling or too many dependencies
- **Duplicate test code**: Extract common setup into helper methods or use data providers
- **External resource dependencies**: Use test doubles to isolate code under test
- **Hard to understand tests**: Refactor for clarity and readability
- **Implementation-coupled tests**: Focus on behavior rather than implementation details
- **Order-dependent tests**: Make tests independent of execution order
- **DateTime dependencies**: Use Symfony Clock or Carbon for testable date/time objects

### Test Structure

```php
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;

class ExampleTest extends TestCase
{
    use ProphecyTrait;
    
    private Calculator $calculator;
    private Incrementor $incrementor;
    private Decrementor $decrementor;
    
    #[Before]
    public function setupTestDoubles(): void
    {
        $this->incrementor = $this->prophesize(Incrementor::class);
        $this->decrementor = $this->prophesize(Decrementor::class);
    }
    
    public function setUp(): void
    {
        // Only use setup for 3+ tests that need common instance
        $this->calculator = new Calculator(
            $this->incrementor->reveal(),
            $this->decrementor->reveal()
        );
    }
    
    #[Test]
    public function it_should_do_something_when_condition(): void
    {
        // Arrange
        $input = 'test data';
        $this->incrementor->increment($input)->willReturn('incremented data');
        
        // Act
        $result = $this->calculator->process($input);
        
        // Assert
        $this->assertEquals('expected', $result);
        $this->incrementor->increment($input)->shouldHaveBeenCalledOnce();
    }
}
```

### Test Doubles with Prophecy

Use Prophecy trait for creating test doubles:

- **Stubs**: Provide predefined responses to method calls
- **Mocks**: Verify that specific methods were called with specific arguments
- **Spies**: Record information about method calls for later verification
- **Fakes**: Provide a simplified implementation of a real object
- **Dummies**: Objects that are passed around but never used

#### Setup Guidelines

- **Don't use setup for less than 3 tests**
- **Use `#[Before]` annotation for creating test doubles**
- **Use `setUp()` only for common instance creation**
- **Create test doubles once and reuse them across tests**

## Coding Philosophy

### Clean Code Principles

1. **Single Responsibility**: Each class/method has one reason to change
2. **Open/Closed**: Open for extension, closed for modification
3. **Dependency Inversion**: Depend on abstractions, not concretions
4. **Interface Segregation**: Small, focused interfaces
5. **Liskov Substitution**: Subtypes are substitutable

### Code Quality Standards

- **Type Safety**: Use strict typing everywhere possible
- **Immutability**: Prefer immutable objects and data structures
- **Composition over Inheritance**: Favor composition for code reuse
- **Fail Fast**: Validate inputs early and throw exceptions for invalid states
- **Defensive Programming**: Handle edge cases and error conditions

### Naming Conventions

- **Classes**: PascalCase (e.g., `AIAgent`, `HttpNode`)
- **Methods**: camelCase (e.g., `executeWorkflow`, `addNode`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DEFAULT_TIMEOUT`)
- **Variables**: camelCase (e.g., `workflowName`, `nodeConfig`)
- **Test Methods**: Use `it_[does_something]` format with `#[Test]` annotation (e.g., `it_should_execute_workflow_when_valid_input_provided`)

## Design Principles

### Core Design Values

#### 1. Composition over Inheritance
- Prefer composition and delegation over inheritance
- Use interfaces and dependency injection for flexibility
- Example: `AIAgent` composes `MemoryInterface` rather than extending a base memory class

#### 2. Code to Interface over Implementation
- Depend on abstractions, not concrete classes
- Use interfaces for all external dependencies
- Example: `AIAgent` depends on `OpenAIClientInterface`, not concrete OpenAI client

#### 3. Tell, Don't Ask
- Objects should tell other objects what to do, not ask for their state
- Encapsulate behavior within objects
- Example: `Workflow` tells nodes to execute rather than asking for their state

#### 4. Law of Demeter
- Objects should only communicate with their immediate neighbors
- Avoid deep object chains: `a.getB().getC().getD()`
- Example: Use method chaining or intermediate objects to reduce coupling

#### 5. Four Rules of Simple Design
- **Tests Pass**: Code must work correctly
- **Reveals Intent**: Code should be self-documenting
- **No Duplication**: DRY principle - eliminate code duplication
- **Fewest Elements**: Minimize classes, methods, and complexity

### Method Design Guidelines

#### Public Methods
- **Keep public methods short** (ideally 5-10 lines)
- Focus on high-level behavior and coordination
- Delegate implementation details to private methods
- Example: Public method orchestrates, private methods implement

#### Private Methods
- **Can be longer and more descriptive**
- Handle complex implementation details
- Use descriptive names that explain the "what" and "why"
- Break down complex logic into smaller, focused methods

### Code Simplicity Guidelines

#### Conservative Refactoring
- **Extract methods and classes conservatively**
- Only extract when it improves readability or reduces duplication
- Prefer simple, readable code over clever abstractions
- Use simple constructs: `foreach` over `array_map` when it's clearer

#### Method Length
- **Target 10 lines or less for most methods**
- Longer methods should be rare and well-justified
- Break down methods that exceed 10-15 lines
- Focus on single responsibility within each method

#### Readability First
- **Code should be simple and accessible**
- Use clear, descriptive variable and method names
- Avoid complex one-liners or clever tricks
- Prefer explicit over implicit behavior

### SOLID Principles

#### Single Responsibility Principle (SRP)
- Each class should have only one reason to change
- Example: `Workflow` handles workflow logic, `Execution` handles execution state

#### Open/Closed Principle (OCP)
- Open for extension, closed for modification
- Example: New node types can be added without modifying existing code

#### Liskov Substitution Principle (LSP)
- Subtypes must be substitutable for their base types
- Example: All nodes must implement the `Node` interface correctly

#### Interface Segregation Principle (ISP)
- Clients should not be forced to depend on interfaces they don't use
- Example: Separate interfaces for `ToolInterface` and `NodeInterface`

#### Dependency Inversion Principle (DIP)
- High-level modules should not depend on low-level modules
- Example: `AIAgent` depends on `OpenAIClientInterface`, not concrete implementation

### Architecture Patterns

#### Node Pattern
- Each processing unit is a separate node
- Nodes are connected through explicit connections
- Nodes can be easily extended and composed

#### Tool Pattern
- Tools provide specific capabilities to AI agents
- Tools follow a consistent interface
- Tools are stateless and reusable

#### Workflow Pattern
- Workflows compose nodes and connections
- Workflows are executable and testable
- Workflows can be serialized and persisted

### Error Handling Strategy

#### Exception Hierarchy
- `PapiException`: Base exception for all Papi-related errors
- `WorkflowException`: Errors related to workflow execution
- `NodeException`: Errors related to node execution
- `ConnectionException`: Errors related to data flow

#### Error Recovery
- Graceful degradation when possible
- Clear error messages with context
- Logging for debugging and monitoring
- Retry mechanisms for transient failures

## Development Workflow

### Feature Development

1. **Create Spec**: Define requirements and acceptance criteria
2. **Write Tests**: Create tests that define the expected behavior
3. **Implement**: Write the minimum code to make tests pass
4. **Refactor**: Clean up code while maintaining functionality
5. **Review**: Self-review and peer review
6. **Document**: Update documentation and examples

### Code Review Checklist

- [ ] Tests are written and passing
- [ ] Code follows PSR-12 standards
- [ ] PHPStan passes with no errors
- [ ] Code coverage is maintained or improved
- [ ] Documentation is updated
- [ ] Error handling is appropriate
- [ ] Performance considerations are addressed

### Commit Standards

#### Commit Message Format
```
type(scope): description

[optional body]

[optional footer]
```

#### Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Test additions or changes
- `chore`: Maintenance tasks

#### Examples
```
feat(ai-agent): add memory support for conversation context

fix(workflow): resolve issue with parallel execution

docs(readme): update installation instructions
```

## Performance Guidelines

### Memory Management
- Use lazy loading for expensive resources
- Implement proper cleanup in destructors
- Monitor memory usage in large workflows
- Use generators for large data sets

### Execution Optimization
- Cache expensive operations
- Use connection pooling for external services
- Implement parallel execution where possible
- Profile and optimize bottlenecks

### Scalability Considerations
- Design for horizontal scaling
- Use stateless components where possible
- Implement proper resource limits
- Consider async/await patterns for I/O operations 