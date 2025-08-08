<?php

namespace Tests\Unit\Nodes\Utility\Memory;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\Utility\Memory\InMemory;
use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Memory;

class InMemoryTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_implement_node_and_memory_interfaces()
    {
        // Arrange
        $memory = new InMemory('memory1', 'Memory Node');
        
        // Act & Assert
        $this->assertInstanceOf(Node::class, $memory);
        $this->assertInstanceOf(Memory::class, $memory);
    }
    
    #[Test]
    public function it_should_add_message_to_memory()
    {
        // Arrange
        $memory = new InMemory('memory1', 'Memory Node');
        
        // Act
        $memory->addMessage('user', 'Hello world');
        
        // Assert
        $messages = $memory->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('user', $messages[0]['role']);
        $this->assertEquals('Hello world', $messages[0]['content']);
    }
    
    #[Test]
    public function it_should_clear_memory()
    {
        // Arrange
        $memory = new InMemory('memory1', 'Memory Node');
        $memory->addMessage('user', 'Hello world');
        
        // Act
        $memory->clear();
        
        // Assert
        $messages = $memory->getMessages();
        $this->assertCount(0, $messages);
    }
    
    #[Test]
    public function it_should_return_context()
    {
        // Arrange
        $memory = new InMemory('memory1', 'Memory Node');
        $memory->addMessage('user', 'Hello world');
        $memory->addMessage('assistant', 'Hi there!');
        
        // Act
        $context = $memory->getContext();
        
        // Assert
        $this->assertCount(2, $context);
        $this->assertEquals('user', $context[0]['role']);
        $this->assertEquals('assistant', $context[1]['role']);
    }
} 
