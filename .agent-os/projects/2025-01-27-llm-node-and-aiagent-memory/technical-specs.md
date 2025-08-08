# Technical Specifications: LLM Node and AIAgent Memory

## Architecture Overview

### Design Principles Applied

This implementation follows the established design principles:

1. **Composition over Inheritance**: Both LLM and AIAgent compose dependencies rather than extending base classes
2. **Code to Interface**: All external dependencies use interfaces (`OpenAIClientInterface`, `MemoryInterface`)
3. **Tell, Don't Ask**: Objects tell other objects what to do rather than asking for state
4. **Law of Demeter**: Methods communicate only with immediate neighbors
5. **Simple Design**: Methods are short, focused, and reveal intent

### LLM Node Design

The LLM node will be a simplified version of the AIAgent, focused solely on text generation without tool-calling capabilities.

```php
class LLMNode extends Node
{
    private OpenAIClientInterface $openAIClient;
    private string $model;
    private array $parameters;
    
    public function execute(array $input): array
    {
        // Generate text response using OpenAI API
        // Return structured response
    }
}
```

### AIAgent Memory System

The memory system will be integrated into the existing AIAgent class, providing conversation context management.

```php
class AIAgent extends Node
{
    private MemoryInterface $memory;
    private array $memoryConfig;
    
    public function execute(array $input): array
    {
        // Retrieve conversation history from memory
        // Include context in API call
        // Store new interaction in memory
        // Return response with updated memory state
    }
}
```

## Detailed Design

### LLM Node Implementation

#### Class Structure
```php
namespace Papi\Core\Integrations\AI;

class LLMNode extends Node
{
    private OpenAIClientInterface $openAIClient;
    private string $model;
    private array $parameters;
    
    public function __construct(string $id, string $name)
    {
        parent::__construct($id, $name);
        $this->initializeDefaultParameters();
    }
    
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    public function setParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }
    
    public function execute(array $input): array
    {
        $prompt = $this->extractPrompt($input);
        $this->validatePrompt($prompt);
        
        $messages = $this->buildMessages($prompt);
        $response = $this->callOpenAI($messages);
        
        return $this->formatResponse($response, $prompt);
    }
    
    private function initializeDefaultParameters(): void
    {
        $this->model = 'gpt-3.5-turbo';
        $this->parameters = [
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ];
    }
    
    private function extractPrompt(array $input): string
    {
        return $input['prompt'] ?? $this->config['prompt'] ?? '';
    }
    
    private function validatePrompt(string $prompt): void
    {
        if (empty($prompt)) {
            throw new \InvalidArgumentException('Prompt is required');
        }
    }
    
    private function buildMessages(string $prompt): array
    {
        return [
            ['role' => 'user', 'content' => $prompt]
        ];
    }
    
    private function callOpenAI(array $messages): array
    {
        $requestData = $this->buildRequestData($messages);
        return $this->openAIClient->createChatCompletion($requestData);
    }
    
    private function buildRequestData(array $messages): array
    {
        return [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->parameters['temperature'],
            'max_tokens' => $this->parameters['max_tokens'],
            'top_p' => $this->parameters['top_p'],
            'frequency_penalty' => $this->parameters['frequency_penalty'],
            'presence_penalty' => $this->parameters['presence_penalty'],
        ];
    }
    
    private function formatResponse(array $response, string $prompt): array
    {
        return [
            'response' => $response['choices'][0]['message']['content'],
            'model' => $this->model,
            'usage' => $response['usage'] ?? [],
            'prompt' => $prompt,
        ];
    }
}
```

#### Configuration Options
- `model`: OpenAI model to use (gpt-3.5-turbo, gpt-4, etc.)
- `temperature`: Controls randomness (0.0 to 2.0)
- `max_tokens`: Maximum tokens in response
- `top_p`: Nucleus sampling parameter
- `frequency_penalty`: Reduces repetition
- `presence_penalty`: Encourages new topics

### AIAgent Memory System

#### Memory Interface
```php
namespace Papi\Core\Agents;

interface MemoryInterface
{
    public function addMessage(string $role, string $content, array $metadata = []): void;
    public function getMessages(int $limit = null): array;
    public function clear(): void;
    public function getContext(int $maxTokens = 4000): array;
    public function persist(string $sessionId): void;
    public function restore(string $sessionId): void;
}
```

#### In-Memory Implementation
```php
namespace Papi\Core\Agents;

class InMemoryMemory implements MemoryInterface
{
    private array $messages = [];
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_messages' => 50,
            'max_tokens' => 4000,
            'retention_policy' => 'sliding_window',
        ], $config);
    }
    
    public function addMessage(string $role, string $content, array $metadata = []): void
    {
        $message = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];
        
        $this->messages[] = $message;
        
        // Apply retention policy
        $this->applyRetentionPolicy();
    }
    
    public function getMessages(int $limit = null): array
    {
        $messages = $this->messages;
        
        if ($limit !== null) {
            $messages = array_slice($messages, -$limit);
        }
        
        return $messages;
    }
    
    public function getContext(int $maxTokens = 4000): array
    {
        $messages = $this->messages;
        $totalTokens = 0;
        $contextMessages = [];
        
        // Start from most recent messages and work backwards
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = $messages[$i];
            $estimatedTokens = strlen($message['content']) / 4; // Rough estimation
            
            if ($totalTokens + $estimatedTokens > $maxTokens) {
                break;
            }
            
            array_unshift($contextMessages, $message);
            $totalTokens += $estimatedTokens;
        }
        
        return $contextMessages;
    }
    
    private function applyRetentionPolicy(): void
    {
        $maxMessages = $this->config['max_messages'];
        
        if (count($this->messages) > $maxMessages) {
            $this->messages = array_slice($this->messages, -$maxMessages);
        }
    }
    
    public function clear(): void
    {
        $this->messages = [];
    }
    
    public function persist(string $sessionId): void
    {
        // Implementation for persistence (file, database, etc.)
    }
    
    public function restore(string $sessionId): void
    {
        // Implementation for restoration
    }
}
```

#### Enhanced AIAgent
```php
namespace Papi\Core\Agents;

class AIAgent extends Node
{
    private OpenAIClientInterface $openAIClient;
    private MemoryInterface $memory;
    private array $memoryConfig;
    private array $tools = [];
    
    public function __construct(string $id, string $name)
    {
        parent::__construct($id, $name);
        $this->initializeMemory();
    }
    
    public function setMemory(MemoryInterface $memory): self
    {
        $this->memory = $memory;
        return $this;
    }
    
    public function setMemoryConfig(array $config): self
    {
        $this->memoryConfig = array_merge($this->memoryConfig, $config);
        return $this;
    }
    
    public function execute(array $input): array
    {
        $userMessage = $this->extractUserMessage($input);
        $this->validateUserMessage($userMessage);
        
        $this->addUserMessageToMemory($userMessage);
        $contextMessages = $this->getConversationContext();
        
        $requestData = $this->buildRequestData($contextMessages);
        $response = $this->callOpenAI($requestData);
        
        $assistantMessage = $response['choices'][0]['message'];
        $this->addAssistantResponseToMemory($assistantMessage);
        
        if ($this->hasToolCalls($assistantMessage)) {
            $assistantMessage = $this->handleToolCalls($assistantMessage);
        }
        
        return $this->formatResponse($response, $contextMessages);
    }
    
    private function initializeMemory(): void
    {
        $this->memory = new InMemoryMemory();
        $this->memoryConfig = [
            'enabled' => true,
            'max_context_tokens' => 4000,
            'include_tool_calls' => true,
        ];
    }
    
    private function extractUserMessage(array $input): string
    {
        return $input['message'] ?? $input['query'] ?? '';
    }
    
    private function validateUserMessage(string $userMessage): void
    {
        if (empty($userMessage)) {
            throw new \InvalidArgumentException('Message or query is required');
        }
    }
    
    private function addUserMessageToMemory(string $userMessage): void
    {
        $this->memory->addMessage('user', $userMessage);
    }
    
    private function getConversationContext(): array
    {
        return $this->memory->getContext($this->memoryConfig['max_context_tokens']);
    }
    
    private function buildRequestData(array $contextMessages): array
    {
        $requestData = [
            'model' => $this->model,
            'messages' => $contextMessages,
            'temperature' => $this->temperature,
        ];
        
        if ($this->hasTools()) {
            $requestData['tools'] = $this->prepareTools();
            $requestData['tool_choice'] = 'auto';
        }
        
        return $requestData;
    }
    
    private function callOpenAI(array $requestData): array
    {
        return $this->openAIClient->createChatCompletion($requestData);
    }
    
    private function addAssistantResponseToMemory(array $assistantMessage): void
    {
        $this->memory->addMessage('assistant', $assistantMessage['content'], [
            'tool_calls' => $assistantMessage['tool_calls'] ?? null,
        ]);
    }
    
    private function hasToolCalls(array $assistantMessage): bool
    {
        return !empty($assistantMessage['tool_calls']);
    }
    
    private function hasTools(): bool
    {
        return !empty($this->tools);
    }
    
    private function handleToolCalls(array $assistantMessage): array
    {
        $toolResults = $this->executeToolCalls($assistantMessage['tool_calls']);
        $this->addToolResultsToMemory($toolResults);
        
        $followUpResponse = $this->makeFollowUpCall($toolResults);
        $finalAssistantMessage = $followUpResponse['choices'][0]['message'];
        
        $this->memory->addMessage('assistant', $finalAssistantMessage['content']);
        
        return $finalAssistantMessage;
    }
    
    private function executeToolCalls(array $toolCalls): array
    {
        $results = [];
        
        foreach ($toolCalls as $toolCall) {
            $result = $this->executeSingleToolCall($toolCall);
            if ($result !== null) {
                $results[] = $result;
            }
        }
        
        return $results;
    }
    
    private function executeSingleToolCall(array $toolCall): ?array
    {
        $toolName = $toolCall['function']['name'];
        $arguments = json_decode($toolCall['function']['arguments'], true);
        
        if (!isset($this->tools[$toolName])) {
            return null;
        }
        
        $tool = $this->tools[$toolName];
        $result = $tool->execute($arguments);
        
        return [
            'tool_call_id' => $toolCall['id'],
            'role' => 'tool',
            'content' => json_encode($result),
        ];
    }
    
    private function addToolResultsToMemory(array $toolResults): void
    {
        foreach ($toolResults as $result) {
            $this->memory->addMessage('tool', $result['content'], [
                'tool_call_id' => $result['tool_call_id'],
            ]);
        }
    }
    
    private function makeFollowUpCall(array $toolResults): array
    {
        $messages = $this->getConversationContext();
        $messages = array_merge($messages, $toolResults);
        
        return $this->openAIClient->createChatCompletion([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
        ]);
    }
    
    private function formatResponse(array $response, array $contextMessages): array
    {
        return [
            'response' => $response['choices'][0]['message']['content'],
            'model' => $this->model,
            'usage' => $response['usage'] ?? [],
            'memory_state' => [
                'message_count' => count($this->memory->getMessages()),
                'context_tokens' => $this->estimateContextTokens($contextMessages),
            ],
        ];
    }
    
    private function estimateContextTokens(array $messages): int
    {
        $totalTokens = 0;
        foreach ($messages as $message) {
            $totalTokens += strlen($message['content']) / 4; // Rough estimation
        }
        return $totalTokens;
    }
}
```

## Data Models

### Message Structure
```php
[
    'role' => 'user|assistant|tool|system',
    'content' => 'message content',
    'timestamp' => 1643241600,
    'metadata' => [
        'tool_calls' => [...],
        'tool_call_id' => '...',
        'session_id' => '...',
    ],
]
```

### Memory Configuration
```php
[
    'enabled' => true,
    'max_messages' => 50,
    'max_context_tokens' => 4000,
    'retention_policy' => 'sliding_window|fixed_window|lru',
    'persistence' => [
        'enabled' => false,
        'driver' => 'file|database|redis',
        'config' => [...],
    ],
]
```

## API Integration

### OpenAI API Usage
- Use `chat/completions` endpoint for both LLM and AIAgent
- Handle rate limiting and retries
- Implement proper error handling
- Support streaming responses (future enhancement)

### Error Handling
```php
try {
    $response = $this->openAIClient->createChatCompletion($requestData);
} catch (RateLimitException $e) {
    // Implement exponential backoff
    sleep(pow(2, $retryCount));
    $retryCount++;
} catch (APIException $e) {
    // Log error and return fallback response
    throw new NodeException('OpenAI API error: ' . $e->getMessage());
}
```

## Performance Considerations

### Memory Management
- Implement efficient message storage
- Use lazy loading for large conversation histories
- Implement smart context truncation
- Cache frequently accessed data

### API Optimization
- Batch API calls where possible
- Implement connection pooling
- Use async requests for parallel processing
- Cache API responses when appropriate

## Security Considerations

### Data Protection
- Encrypt sensitive data in memory
- Implement secure persistence
- Sanitize user inputs
- Log access to sensitive data

### API Security
- Secure API key management
- Implement request signing
- Validate API responses
- Handle API key rotation

## Testing Strategy

### Unit Tests
- Test LLM node with Prophecy test doubles
- Test memory system with various configurations
- Test AIAgent with and without tools
- Test error handling scenarios
- Follow TDD cycle: Red-Green-Refactor
- Use `it_[does_something]` naming convention with `#[Test]` annotations

### Integration Tests
- Test complete workflow with LLM node
- Test AIAgent memory across multiple interactions
- Test memory persistence and restoration
- Test performance under load
- Use Prophecy for external dependencies

### Test Doubles Strategy
- **Stubs**: For OpenAI API responses and external services
- **Mocks**: For verifying API calls and method invocations
- **Spies**: For tracking memory operations and data flow
- **Fakes**: For simplified implementations of complex dependencies

### Test Implementations with Prophecy
```php
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;

class LLMNodeTest extends TestCase
{
    use ProphecyTrait;
    
    private LLMNode $llmNode;
    private OpenAIClientInterface $openAIClient;
    
    #[Before]
    public function setupTestDoubles(): void
    {
        $this->openAIClient = $this->prophesize(OpenAIClientInterface::class);
    }
    
    public function setUp(): void
    {
        $this->llmNode = new LLMNode('test', 'Test LLM');
        $this->llmNode->setOpenAIClient($this->openAIClient->reveal());
    }
    
    #[Test]
    public function it_should_generate_text_response_when_valid_prompt_provided(): void
    {
        // Arrange
        $prompt = 'Hello, how are you?';
        $expectedResponse = 'I am doing well, thank you!';
        
        $this->openAIClient->createChatCompletion([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
        ])->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => $expectedResponse,
                        'role' => 'assistant',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ]);
        
        // Act
        $result = $this->llmNode->execute(['prompt' => $prompt]);
        
        // Assert
        $this->assertEquals($expectedResponse, $result['response']);
        $this->assertEquals('gpt-3.5-turbo', $result['model']);
        $this->openAIClient->createChatCompletion(Argument::any())->shouldHaveBeenCalledOnce();
    }
    
    #[Test]
    public function it_should_throw_exception_when_prompt_is_empty(): void
    {
        // Arrange
        $prompt = '';
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt is required');
        
        $this->llmNode->execute(['prompt' => $prompt]);
    }
}
``` 