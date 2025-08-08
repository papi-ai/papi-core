<?php

namespace Tests\Unit\Nodes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\Tool;

class ToolInterfaceTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_define_tool_interface_methods()
    {
        // Arrange
        $tool = $this->prophesize(Tool::class);
        
        // Act & Assert - Verify interface methods exist
        $this->assertTrue(method_exists($tool->reveal(), 'getToolSchema'));
        $this->assertTrue(method_exists($tool->reveal(), 'getToolName'));
        $this->assertTrue(method_exists($tool->reveal(), 'getToolDescription'));
    }
} 
