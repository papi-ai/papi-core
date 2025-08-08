<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Papi\Core\Connection;

class ConnectionTest extends TestCase
{
    #[Test]
    public function it_creates_a_connection(): void
    {
        $connection = new Connection('node1', 'node2');

        $this->assertSame('node1', $connection->getSourceNode());
        $this->assertSame('node2', $connection->getTargetNode());
        $this->assertSame('output', $connection->getSourceOutput());
        $this->assertSame('input', $connection->getTargetInput());
    }

    #[Test]
    public function it_creates_a_connection_with_custom_ports(): void
    {
        $connection = new Connection('node1', 'node2', 'custom_output', 'custom_input');

        $this->assertSame('node1', $connection->getSourceNode());
        $this->assertSame('node2', $connection->getTargetNode());
        $this->assertSame('custom_output', $connection->getSourceOutput());
        $this->assertSame('custom_input', $connection->getTargetInput());
    }

    #[Test]
    public function it_sets_and_gets_transform(): void
    {
        $connection = new Connection('node1', 'node2');
        $transform = ['field' => 'value'];

        $connection->setTransform($transform);
        $this->assertSame($transform, $connection->getTransform());
    }

    #[Test]
    public function it_converts_connection_to_array(): void
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
