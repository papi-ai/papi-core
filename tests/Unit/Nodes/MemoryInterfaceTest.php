<?php

namespace Tests\Unit\Nodes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\Memory;

class MemoryInterfaceTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_define_memory_interface_methods()
    {
        // Arrange
        $memory = $this->prophesize(Memory::class);
        
        // Act & Assert - Verify interface methods exist
        $this->assertTrue(method_exists($memory->reveal(), 'addMessage'));
        $this->assertTrue(method_exists($memory->reveal(), 'getMessages'));
        $this->assertTrue(method_exists($memory->reveal(), 'clear'));
        $this->assertTrue(method_exists($memory->reveal(), 'getContext'));
    }
} 
