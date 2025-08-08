# Trigger System - Technical Specifications

## Architecture Overview

The Trigger System consists of a base interface and concrete implementations for different trigger types. All trigger nodes extend the base `Node` class and implement the `TriggerInterface`.

### Design Principles Applied
- **Composition over Inheritance**: Trigger nodes compose behavior rather than inherit
- **Code to Interface**: All trigger nodes implement `TriggerInterface`
- **Tell, Don't Ask**: Trigger nodes encapsulate their trigger logic
- **Law of Demeter**: Trigger nodes interact minimally with external dependencies
- **Four Rules of Simple Design**: Tests pass, reveals intent, no duplication, fewest elements

## Core Components

### TriggerInterface
```php
<?php

namespace Papi\Core\Triggers;

use Papi\Core\Node;

interface TriggerInterface
{
    /**
     * Validate trigger configuration
     */
    public function validateConfiguration(): bool;
    
    /**
     * Get trigger type identifier
     */
    public function getTriggerType(): string;
    
    /**
     * Check if trigger is ready to fire
     */
    public function isReady(): bool;
}
```

### Base Trigger Node
```php
<?php

namespace Papi\Core\Triggers;

use Papi\Core\Node;

abstract class BaseTriggerNode extends Node implements TriggerInterface
{
    protected array $triggerConfig = [];
    
    public function __construct(string $id, string $name, array $config = [])
    {
        parent::__construct($id, $name);
        $this->triggerConfig = $config;
        $this->validateConfiguration();
    }
    
    public function execute(array $input = []): array
    {
        // Triggers don't accept input - they are entry points
        if (!empty($input)) {
            throw new \InvalidArgumentException('Trigger nodes do not accept input');
        }
        
        return $this->processTrigger();
    }
    
    abstract protected function processTrigger(): array;
    
    public function validateConfiguration(): bool
    {
        // Base validation - subclasses can override
        return true;
    }
    
    public function isReady(): bool
    {
        return $this->validateConfiguration();
    }
}
```

## Concrete Trigger Implementations

### ChatTriggerNode
```php
<?php

namespace Papi\Core\Triggers;

class ChatTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'chat';
    }
    
    protected function processTrigger(): array
    {
        $message = $this->triggerConfig['message'] ?? '';
        $sender = $this->triggerConfig['sender'] ?? 'unknown';
        $channel = $this->triggerConfig['channel'] ?? 'default';
        
        return [
            'type' => 'chat_message',
            'content' => $message,
            'sender' => $sender,
            'channel' => $channel,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        if (empty($this->triggerConfig['message'])) {
            throw new \InvalidArgumentException('Chat trigger requires a message');
        }
        
        return true;
    }
}
```

### EmailTriggerNode
```php
<?php

namespace Papi\Core\Triggers;

class EmailTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'email';
    }
    
    protected function processTrigger(): array
    {
        $subject = $this->triggerConfig['subject'] ?? '';
        $body = $this->triggerConfig['body'] ?? '';
        $sender = $this->triggerConfig['sender'] ?? '';
        $recipients = $this->triggerConfig['recipients'] ?? [];
        
        return [
            'type' => 'email',
            'subject' => $subject,
            'body' => $body,
            'sender' => $sender,
            'recipients' => $recipients,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        if (empty($this->triggerConfig['subject']) && empty($this->triggerConfig['body'])) {
            throw new \InvalidArgumentException('Email trigger requires subject or body');
        }
        
        return true;
    }
}
```

### ManualTriggerNode
```php
<?php

namespace Papi\Core\Triggers;

class ManualTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'manual';
    }
    
    protected function processTrigger(): array
    {
        $query = $this->triggerConfig['query'] ?? '';
        $user = $this->triggerConfig['user'] ?? 'manual';
        
        return [
            'type' => 'manual_trigger',
            'query' => $query,
            'user' => $user,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        // Manual triggers can be empty - they're for manual input
        return true;
    }
}
```

## Workflow Integration

### Workflow Validation
The `Workflow` class needs to be updated to validate trigger node placement:

```php
public function validateTriggerPlacement(): bool
{
    $triggerNodes = $this->getTriggerNodes();
    
    foreach ($triggerNodes as $triggerNode) {
        $incomingConnections = $this->getIncomingConnections($triggerNode->getId());
        
        if (!empty($incomingConnections)) {
            throw new \InvalidArgumentException(
                "Trigger node '{$triggerNode->getId()}' cannot have incoming connections"
            );
        }
    }
    
    return true;
}

private function getTriggerNodes(): array
{
    return array_filter($this->nodes, function ($node) {
        return $node instanceof TriggerInterface;
    });
}
```

## Configuration Examples

### Chat Trigger Configuration
```php
$chatTrigger = new ChatTriggerNode('chat_trigger', 'Chat Message', [
    'message' => 'Hello, how can I help you?',
    'sender' => 'user123',
    'channel' => 'support'
]);
```

### Email Trigger Configuration
```php
$emailTrigger = new EmailTriggerNode('email_trigger', 'Email Notification', [
    'subject' => 'New support ticket',
    'body' => 'A new support ticket has been created',
    'sender' => 'noreply@example.com',
    'recipients' => ['support@example.com']
]);
```

### Manual Trigger Configuration
```php
$manualTrigger = new ManualTriggerNode('manual_trigger', 'Manual Input', [
    'query' => 'Process this data',
    'user' => 'admin'
]);
```

## Testing Strategy

### Unit Tests with TDD
Following the established TDD cycle and using Prophecy for test doubles:

```php
<?php

namespace Papi\Core\Tests\Unit\Triggers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Triggers\ChatTriggerNode;

class ChatTriggerNodeTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_create_chat_trigger_with_valid_configuration()
    {
        $config = [
            'message' => 'Test message',
            'sender' => 'user123'
        ];
        
        $trigger = new ChatTriggerNode('test', 'Test Trigger', $config);
        
        $this->assertEquals('chat', $trigger->getTriggerType());
        $this->assertTrue($trigger->isReady());
    }
    
    #[Test]
    public function it_should_throw_exception_when_message_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chat trigger requires a message');
        
        new ChatTriggerNode('test', 'Test Trigger', []);
    }
    
    #[Test]
    public function it_should_output_structured_chat_data()
    {
        $config = [
            'message' => 'Hello world',
            'sender' => 'user123',
            'channel' => 'general'
        ];
        
        $trigger = new ChatTriggerNode('test', 'Test Trigger', $config);
        $output = $trigger->execute();
        
        $this->assertEquals('chat_message', $output['type']);
        $this->assertEquals('Hello world', $output['content']);
        $this->assertEquals('user123', $output['sender']);
        $this->assertEquals('general', $output['channel']);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertArrayHasKey('metadata', $output);
    }
    
    #[Test]
    public function it_should_reject_input_data()
    {
        $trigger = new ChatTriggerNode('test', 'Test Trigger', ['message' => 'test']);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Trigger nodes do not accept input');
        
        $trigger->execute(['some' => 'input']);
    }
}
```

## Integration with Existing System

### Workflow Usage
```php
// Create trigger node
$chatTrigger = new ChatTriggerNode('chat_trigger', 'Chat Input', [
    'message' => 'User message here',
    'sender' => 'user123'
]);

// Create AI agent to process chat
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant.');

// Create workflow
$workflow = new Workflow('chat_workflow');
$workflow->addNode($chatTrigger);
$workflow->addNode($aiAgent);
$workflow->addConnection(new Connection('chat_trigger', 'assistant'));

// Execute workflow (trigger provides the input)
$execution = $workflow->execute();
```

## Future Extensibility

### Additional Trigger Types
- **Webhook Trigger**: Listen for HTTP webhook calls
- **Schedule Trigger**: Time-based workflow initiation
- **Database Trigger**: Database event-driven workflows
- **File Trigger**: File system event workflows

### Advanced Features
- **Trigger Filtering**: Filter triggers based on conditions
- **Trigger Transformation**: Transform trigger data before processing
- **Trigger Persistence**: Store trigger state for recovery
- **Trigger Monitoring**: Monitor trigger performance and health 