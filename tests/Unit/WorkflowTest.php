<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\Node;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    #[Test]
    public function it_creates_a_workflow(): void
    {
        $workflow = new Workflow('Test Workflow');

        $this->assertSame('Test Workflow', $workflow->getName());
        $this->assertEmpty($workflow->getNodes());
        $this->assertEmpty($workflow->getConnections());
    }

    #[Test]
    public function it_adds_a_node(): void
    {
        $workflow = new Workflow('Test Workflow');
        $node = new class ('test-node', 'Test Node') implements Node {
            private string $id;
            private string $name;
            
            public function __construct(string $id, string $name)
            {
                $this->id = $id;
                $this->name = $name;
            }
            
            public function execute(array $input): array
            {
                return ['result' => 'test'];
            }
            
            public function getId(): string
            {
                return $this->id;
            }
            
            public function getName(): string
            {
                return $this->name;
            }
            
            public function toArray(): array
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'type' => 'test_node'
                ];
            }
        };

        $workflow->addNode($node);

        $this->assertCount(1, $workflow->getNodes());
        $this->assertSame($node, $workflow->getNodes()['test-node']);
    }

    #[Test]
    public function it_adds_a_connection(): void
    {
        $workflow = new Workflow('Test Workflow');
        $connection = new Connection('node1', 'node2');

        $workflow->addConnection($connection);

        $this->assertCount(1, $workflow->getConnections());
        $this->assertSame($connection, $workflow->getConnections()[0]);
    }

    #[Test]
    public function it_converts_workflow_to_array(): void
    {
        $workflow = new Workflow('Test Workflow');
        $node = new class ('test-node', 'Test Node') implements Node {
            private string $id;
            private string $name;
            
            public function __construct(string $id, string $name)
            {
                $this->id = $id;
                $this->name = $name;
            }
            
            public function execute(array $input): array
            {
                return ['result' => 'test'];
            }
            
            public function getId(): string
            {
                return $this->id;
            }
            
            public function getName(): string
            {
                return $this->name;
            }
            
            public function toArray(): array
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'type' => 'test_node'
                ];
            }
        };
        $connection = new Connection('node1', 'node2');

        $workflow->addNode($node);
        $workflow->addConnection($connection);

        $array = $workflow->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('nodes', $array);
        $this->assertArrayHasKey('connections', $array);
        $this->assertArrayHasKey('executionMode', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('Test Workflow', $array['name']);
        $this->assertCount(1, $array['nodes']);
        $this->assertCount(1, $array['connections']);
    }

    #[Test]
    public function it_validates_a_workflow(): void
    {
        $workflow = new Workflow('Test Workflow');

        $this->assertTrue($workflow->validate());
    }
}
