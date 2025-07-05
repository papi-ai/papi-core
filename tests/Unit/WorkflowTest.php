<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Node;

class WorkflowTest extends TestCase
{
    public function testWorkflowCreation(): void
    {
        $workflow = new Workflow('Test Workflow');
        
        $this->assertSame('Test Workflow', $workflow->getName());
        $this->assertEmpty($workflow->getNodes());
        $this->assertEmpty($workflow->getConnections());
    }

    public function testAddingNode(): void
    {
        $workflow = new Workflow('Test Workflow');
        $node = new class('test-node', 'Test Node') extends Node {
            public function execute(array $input): array { return ['result' => 'test']; }
        };
        
        $workflow->addNode($node);
        
        $this->assertCount(1, $workflow->getNodes());
        $this->assertSame($node, $workflow->getNodes()['test-node']);
    }

    public function testAddingConnection(): void
    {
        $workflow = new Workflow('Test Workflow');
        $connection = new Connection('node1', 'node2');
        
        $workflow->addConnection($connection);
        
        $this->assertCount(1, $workflow->getConnections());
        $this->assertSame($connection, $workflow->getConnections()[0]);
    }

    public function testWorkflowToArray(): void
    {
        $workflow = new Workflow('Test Workflow');
        $node = new class('test-node', 'Test Node') extends Node {
            public function execute(array $input): array { return ['result' => 'test']; }
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

    public function testWorkflowValidation(): void
    {
        $workflow = new Workflow('Test Workflow');
        
        $this->assertTrue($workflow->validate());
    }
} 
