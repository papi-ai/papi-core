<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papi\Core\Connection;

class ConnectionTest extends TestCase
{
    public function testConnectionCreation(): void
    {
        $connection = new Connection('node1', 'node2');
        
        $this->assertSame('node1', $connection->getSourceNode());
        $this->assertSame('node2', $connection->getTargetNode());
        $this->assertSame('output', $connection->getSourceOutput());
        $this->assertSame('input', $connection->getTargetInput());
    }

    public function testConnectionWithCustomPorts(): void
    {
        $connection = new Connection('node1', 'node2', 'custom_output', 'custom_input');
        
        $this->assertSame('node1', $connection->getSourceNode());
        $this->assertSame('node2', $connection->getTargetNode());
        $this->assertSame('custom_output', $connection->getSourceOutput());
        $this->assertSame('custom_input', $connection->getTargetInput());
    }

    public function testConnectionTransform(): void
    {
        $connection = new Connection('node1', 'node2');
        $transform = ['field' => 'value'];
        
        $connection->setTransform($transform);
        $this->assertSame($transform, $connection->getTransform());
    }

    public function testConnectionToArray(): void
    {
        $connection = new Connection('node1', 'node2', 'output1', 'input1');
        $connection->setTransform(['test' => 'value']);
        
        $array = $connection->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('sourceNode', $array);
        $this->assertArrayHasKey('targetNode', $array);
        $this->assertArrayHasKey('sourceOutput', $array);
        $this->assertArrayHasKey('targetInput', $array);
        $this->assertArrayHasKey('transform', $array);
        
        $this->assertSame('node1', $array['sourceNode']);
        $this->assertSame('node2', $array['targetNode']);
        $this->assertSame('output1', $array['sourceOutput']);
        $this->assertSame('input1', $array['targetInput']);
        $this->assertSame(['test' => 'value'], $array['transform']);
    }
} 
