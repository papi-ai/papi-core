<?php

namespace Tests\Unit\Nodes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\Node;

class NodeInterfaceTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_define_node_interface_methods()
    {
        // Arrange
        $node = $this->prophesize(Node::class);
        
        // Act & Assert - Verify interface methods exist
        $this->assertTrue(method_exists($node->reveal(), 'execute'));
        $this->assertTrue(method_exists($node->reveal(), 'getId'));
        $this->assertTrue(method_exists($node->reveal(), 'getName'));
    }
} 
