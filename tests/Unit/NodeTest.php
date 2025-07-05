<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papi\Core\Node;

class NodeTest extends TestCase
{
    public function testNodePropertiesAndMethods(): void
    {
        $node = new class('test-node', 'Test Node') extends Node {
            public function execute(array $input): array { return ['executed' => true, 'input' => $input]; }
        };

        $this->assertSame('test-node', $node->getId());
        $this->assertSame('Test Node', $node->getName());
        $this->assertSame([], $node->getConfig());

        $node->setConfig(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $node->getConfig());

        $result = $node->execute(['test' => 'data']);
        $this->assertSame(['executed' => true, 'input' => ['test' => 'data']], $result);

        $this->assertTrue($node->validate());
        $this->assertSame([], $node->getInputSchema());
        $this->assertSame([], $node->getOutputSchema());
    }
} 
