<?php

namespace Papi\Core;

/**
 * Execution - Represents the result of a workflow execution
 *
 * Contains information about the execution status, input/output data,
 * and results from individual nodes.
 */
class Execution
{
    private string $id;
    private string $workflowId;
    private string $status;
    private array $inputData;
    private array $outputData = [];
    private array $nodeResults = [];
    private ?string $errorMessage = null;
    private float $startedAt;
    private ?float $completedAt = null;

    public function __construct(string $workflowId, string $status, array $inputData)
    {
        $this->id = uniqid('exec_');
        $this->workflowId = $workflowId;
        $this->status = $status;
        $this->inputData = $inputData;
        $this->startedAt = microtime(true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getInputData(): array
    {
        return $this->inputData;
    }

    public function getOutputData(): array
    {
        return $this->outputData;
    }

    public function setOutputData(array $outputData): self
    {
        $this->outputData = $outputData;
        return $this;
    }

    public function getNodeResults(): array
    {
        return $this->nodeResults;
    }

    public function addNodeResult(string $nodeId, array $result): self
    {
        $this->nodeResults[$nodeId] = $result;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getStartedAt(): float
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?float
    {
        return $this->completedAt;
    }

    public function complete(): self
    {
        $this->completedAt = microtime(true);
        return $this;
    }

    public function getDuration(): float
    {
        $endTime = $this->completedAt ?? microtime(true);
        return ($endTime - $this->startedAt) * 1000; // Convert to milliseconds
    }

    public function getOutput(): array
    {
        return $this->outputData;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'workflowId' => $this->workflowId,
            'status' => $this->status,
            'inputData' => $this->inputData,
            'outputData' => $this->outputData,
            'nodeResults' => $this->nodeResults,
            'errorMessage' => $this->errorMessage,
            'startedAt' => $this->startedAt,
            'completedAt' => $this->completedAt,
            'duration' => $this->getDuration()
        ];
    }
}
