<?php

declare(strict_types=1);

namespace Papi\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Papi\Core\Execution;

class ExecutionTest extends TestCase
{
    #[Test]
    public function it_creates_an_execution(): void
    {
        $execution = new Execution('workflow-123', 'success', ['input' => 'data']);

        $this->assertSame('workflow-123', $execution->getWorkflowId());
        $this->assertSame('success', $execution->getStatus());
        $this->assertSame(['input' => 'data'], $execution->getInputData());
        $this->assertEmpty($execution->getOutputData());
        $this->assertEmpty($execution->getNodeResults());
    }

    #[Test]
    public function it_sets_and_gets_output_data(): void
    {
        $execution = new Execution('workflow-123', 'success', []);
        $outputData = ['result' => 'success'];

        $execution->setOutputData($outputData);

        $this->assertSame($outputData, $execution->getOutputData());
    }

    #[Test]
    public function it_adds_and_gets_node_results(): void
    {
        $execution = new Execution('workflow-123', 'success', []);
        $nodeResult = ['status' => 'success', 'data' => 'test'];

        $execution->addNodeResult('node1', $nodeResult);

        $this->assertCount(1, $execution->getNodeResults());
        $this->assertSame($nodeResult, $execution->getNodeResults()['node1']);
    }

    #[Test]
    public function it_sets_and_gets_error_message(): void
    {
        $execution = new Execution('workflow-123', 'error', []);
        $errorMessage = 'Something went wrong';

        $execution->setErrorMessage($errorMessage);

        $this->assertSame($errorMessage, $execution->getErrorMessage());
    }

    #[Test]
    public function it_completes_execution_and_sets_completed_at(): void
    {
        $execution = new Execution('workflow-123', 'success', []);

        $this->assertNull($execution->getCompletedAt());

        $execution->complete();

        $this->assertNotNull($execution->getCompletedAt());
        $this->assertGreaterThan(0, $execution->getDuration());
    }

    #[Test]
    public function it_converts_execution_to_array(): void
    {
        $execution = new Execution('workflow-123', 'success', ['input' => 'data']);
        $execution->setOutputData(['output' => 'result']);
        $execution->addNodeResult('node1', ['status' => 'success']);
        $execution->complete();

        $array = $execution->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('workflowId', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('inputData', $array);
        $this->assertArrayHasKey('outputData', $array);
        $this->assertArrayHasKey('nodeResults', $array);
        $this->assertArrayHasKey('errorMessage', $array);
        $this->assertArrayHasKey('startedAt', $array);
        $this->assertArrayHasKey('completedAt', $array);
        $this->assertArrayHasKey('duration', $array);

        $this->assertSame('workflow-123', $array['workflowId']);
        $this->assertSame('success', $array['status']);
        $this->assertSame(['input' => 'data'], $array['inputData']);
        $this->assertSame(['output' => 'result'], $array['outputData']);
        $this->assertCount(1, $array['nodeResults']);
    }
}
