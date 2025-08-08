<?php

namespace Tests\Unit\Nodes\AI;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\AI\LLM;
use Papi\Core\Integrations\MockOpenAIClient;
use Papi\Core\Nodes\Node;

class LLMTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_should_implement_node_interface()
    {
        // Arrange
        $llm = new LLM('llm1', 'LLM Node');

        // Act & Assert
        $this->assertInstanceOf(Node::class, $llm);
    }

    #[Test]
    public function it_should_execute_with_mock_client()
    {
        // Arrange
        $mockClient = new MockOpenAIClient(['Test query' => 'Mock response']);
        $llm = new LLM('llm1', 'LLM Node');
        $llm->setLLMClient($mockClient);

        // Act
        $result = $llm->execute(['query' => 'Test query']);

        // Assert
        $this->assertEquals('Mock response', $result['response']);
        $this->assertEquals('llm', $result['metadata']['node_type']);
        $this->assertEquals('', $result['model']); // No default model now
    }

    #[Test]
    public function it_should_configure_model_and_prompt()
    {
        // Arrange
        $mockClient = new MockOpenAIClient(['Hello' => 'Hi there!']);
        $llm = new LLM('llm1', 'LLM Node', [
            'model' => 'gpt-4',
            'system_prompt' => 'You are a helpful assistant'
        ]);
        $llm->setLLMClient($mockClient);

        // Act
        $result = $llm->execute(['query' => 'Hello']);

        // Assert
        $this->assertEquals('gpt-4', $result['model']);
        $this->assertEquals('Hi there!', $result['response']);
    }
}
