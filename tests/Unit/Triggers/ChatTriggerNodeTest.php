<?php

namespace Tests\Unit\Triggers;

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
        // Arrange
        $config = [
            'message' => 'Hello, how can I help you?',
            'sender' => 'user123',
            'channel' => 'support'
        ];
        
        // Act
        $trigger = new ChatTriggerNode('test', 'Test Chat Trigger', $config);
        
        // Assert
        $this->assertEquals('chat', $trigger->getTriggerType());
        $this->assertTrue($trigger->isReady());
    }

    #[Test]
    public function it_should_throw_exception_when_message_is_missing()
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chat trigger requires a message');
        
        new ChatTriggerNode('test', 'Test Chat Trigger', []);
    }

    #[Test]
    public function it_should_output_structured_chat_data()
    {
        // Arrange
        $config = [
            'message' => 'Hello world',
            'sender' => 'user123',
            'channel' => 'general'
        ];
        
        $trigger = new ChatTriggerNode('test', 'Test Chat Trigger', $config);
        
        // Act
        $output = $trigger->execute();
        
        // Assert
        $this->assertEquals('chat_message', $output['type']);
        $this->assertEquals('Hello world', $output['content']);
        $this->assertEquals('user123', $output['sender']);
        $this->assertEquals('general', $output['channel']);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertArrayHasKey('metadata', $output);
    }
} 
