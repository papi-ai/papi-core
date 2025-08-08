<?php

namespace Tests\Unit\Nodes\AI;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Memory\InMemory;
use Papi\Core\Integrations\MockOpenAIClient;
use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;
use Papi\Core\Nodes\Memory;

class AIAgentTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_should_implement_node_interface()
    {
        // Arrange
        $aiAgent = new AIAgent('ai1', 'AI Agent');

        // Act & Assert
        $this->assertInstanceOf(Node::class, $aiAgent);
    }

    #[Test]
    public function it_should_add_tool_node()
    {
        // Arrange
        $aiAgent = new AIAgent('ai1', 'AI Agent');
        $mockToolNode = $this->createMockToolNode();

        // Act
        $result = $aiAgent->addTool($mockToolNode);

        // Assert
        $this->assertSame($aiAgent, $result);
    }

    #[Test]
    public function it_should_throw_exception_when_adding_non_tool_node()
    {
        // Arrange
        $aiAgent = new AIAgent('ai1', 'AI Agent');
        $nonToolNode = $this->prophesize(Node::class)->reveal();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Node must implement Tool interface to be used as a tool');

        $aiAgent->addTool($nonToolNode);
    }

    #[Test]
    public function it_should_set_memory_node()
    {
        // Arrange
        $aiAgent = new AIAgent('ai1', 'AI Agent');
        $memoryNode = new InMemory('memory1', 'Memory Node');

        // Act
        $result = $aiAgent->setMemory($memoryNode);

        // Assert
        $this->assertSame($aiAgent, $result);
    }

    #[Test]
    public function it_should_throw_exception_when_setting_non_memory_node()
    {
        // Arrange
        $aiAgent = new AIAgent('ai1', 'AI Agent');
        $nonMemoryNode = $this->prophesize(Node::class)->reveal();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Node must implement Memory interface to be used as memory');

        $aiAgent->setMemory($nonMemoryNode);
    }

    #[Test]
    public function it_should_execute_with_mock_client()
    {
        // Arrange
        $mockClient = new MockOpenAIClient(['Test query' => 'Mock response']);
        $aiAgent = new AIAgent('ai1', 'AI Agent');
        $aiAgent->setLLMClient($mockClient);

        // Act
        $result = $aiAgent->execute(['query' => 'Test query']);

        // Assert
        $this->assertEquals('Mock response', $result['response']);
        $this->assertEquals('ai_agent', $result['metadata']['node_type']);
    }

    private function createMockToolNode(): Node
    {
        return new class ('mock_tool', 'Mock Tool') implements Node, Tool {
            private string $id;
            private string $name;

            public function __construct(string $id, string $name)
            {
                $this->id = $id;
                $this->name = $name;
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function execute(array $input): array
            {
                return ['result' => 'mock_result'];
            }

            public function getToolSchema(): array
            {
                return [
                    'name' => 'mock_tool',
                    'description' => 'A mock tool for testing',
                    'parameters' => []
                ];
            }

            public function getToolName(): string
            {
                return 'mock_tool';
            }

            public function getToolDescription(): string
            {
                return 'A mock tool for testing';
            }

            public function toArray(): array
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'type' => 'mock_tool'
                ];
            }
        };
    }
}
