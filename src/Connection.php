<?php

namespace Papi\Core;

/**
 * Connection - Links nodes in a workflow
 *
 * A connection defines how data flows from one node to another
 * in a workflow execution.
 */
class Connection
{
    private string $id;
    private string $sourceNode;
    private string $targetNode;
    private string $sourceOutput;
    private string $targetInput;
    private array $transform = [];

    public function __construct(string $sourceNode, string $targetNode, string $sourceOutput = 'output', string $targetInput = 'input')
    {
        $this->id = uniqid('conn_');
        $this->sourceNode = $sourceNode;
        $this->targetNode = $targetNode;
        $this->sourceOutput = $sourceOutput;
        $this->targetInput = $targetInput;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceNode(): string
    {
        return $this->sourceNode;
    }

    public function getTargetNode(): string
    {
        return $this->targetNode;
    }

    public function getSourceOutput(): string
    {
        return $this->sourceOutput;
    }

    public function getTargetInput(): string
    {
        return $this->targetInput;
    }

    public function setTransform(array $transform): self
    {
        $this->transform = $transform;
        return $this;
    }

    public function getTransform(): array
    {
        return $this->transform;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sourceNode' => $this->sourceNode,
            'sourceOutput' => $this->sourceOutput,
            'targetNode' => $this->targetNode,
            'targetInput' => $this->targetInput,
            'transform' => $this->transform
        ];
    }
}
