<?php

namespace Papi\Core;

use Papi\Core\Nodes\Node;

/**
 * Workflow - Main container for workflow logic
 *
 * A workflow is a collection of nodes connected by connections
 * that can be executed to perform automated tasks.
 */
class Workflow
{
    private string $id;
    private string $name;
    /** @var Node[] */
    private array $nodes = [];
    /** @var Connection[] */
    private array $connections = [];
    private string $executionMode = 'sequential';
    private array $metadata = [];

    public function __construct(string $name)
    {
        $this->id = uniqid('workflow_');
        $this->name = $name;
    }

    /**
     * Add a node to the workflow.
     */
    public function addNode(Node $node): self
    {
        $this->nodes[$node->getId()] = $node;
        return $this;
    }

    /**
     * Add a connection between nodes.
     */
    public function addConnection(Connection $connection): self
    {
        $this->connections[] = $connection;
        return $this;
    }

    /**
     * Execute the workflow, passing data through nodes in connection order.
     * Returns an Execution object with results and output.
     */
    public function execute(array $input = []): Execution
    {
        $execution = new Execution($this->id, 'success', $input);
        $nodeResults = [];
        $visited = [];

        // Build a map of incoming connections for each node
        $incoming = [];
        foreach ($this->connections as $conn) {
            $incoming[$conn->getTargetNode()][] = $conn->getSourceNode();
        }
        // Find start nodes (no incoming connections)
        $startNodes = array_diff(array_keys($this->nodes), array_keys($incoming));
        if (empty($startNodes)) {
            $startNodes = array_keys($this->nodes); // fallback: all nodes
        }

        $currentNodes = $startNodes;
        $lastOutput = $input;
        while (!empty($currentNodes)) {
            $nextNodes = [];
            foreach ($currentNodes as $nodeId) {
                if (isset($visited[$nodeId])) {
                    continue;
                }
                $node = $this->nodes[$nodeId];
                $nodeInput = $lastOutput;
                // Use output from previous node if available
                foreach ($this->connections as $conn) {
                    if ($conn->getTargetNode() === $nodeId && isset($nodeResults[$conn->getSourceNode()])) {
                        $nodeInput = $nodeResults[$conn->getSourceNode()]['data'] ?? $lastOutput;
                    }
                }
                try {
                    $result = $node->execute($nodeInput);
                } catch (\Throwable $e) {
                    $result = [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'data' => null,
                        'duration' => 0
                    ];
                    $execution->setErrorMessage($e->getMessage());
                    $execution->setOutputData([]);
                    $execution->complete();
                    return $execution;
                }
                $nodeResults[$nodeId] = $result;
                $execution->addNodeResult($nodeId, $result);
                $lastOutput = $result['data'] ?? $lastOutput;
                $visited[$nodeId] = true;
                // Find next nodes
                foreach ($this->connections as $conn) {
                    if ($conn->getSourceNode() === $nodeId) {
                        $nextNodes[] = $conn->getTargetNode();
                    }
                }
            }
            $currentNodes = $nextNodes;
        }
        // Set output data (prefer last array output)
        $finalOutput = $lastOutput;
        if (!is_array($finalOutput)) {
            foreach (array_reverse($nodeResults) as $result) {
                if (isset($result['data']) && is_array($result['data'])) {
                    $finalOutput = $result['data'];
                    break;
                }
            }
        }
        // Always pass an array to setOutputData
        if (!is_array($finalOutput)) {
            $finalOutput = ['result' => $finalOutput];
        }
        $execution->setOutputData($finalOutput);
        $execution->complete();
        return $execution;
    }

    public function validate(): bool
    {
        // TODO: Implement workflow validation logic
        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nodes' => array_map(fn($node) => $node->toArray(), $this->nodes),
            'connections' => array_map(fn($conn) => $conn->toArray(), $this->connections),
            'executionMode' => $this->executionMode,
            'metadata' => $this->metadata
        ];
    }

    public static function fromArray(array $data): self
    {
        $workflow = new self($data['name']);
        $workflow->id = $data['id'];
        $workflow->executionMode = $data['executionMode'];
        $workflow->metadata = $data['metadata'];
        // TODO: Reconstruct nodes and connections from array data
        return $workflow;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Node[] */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /** @return Connection[] */
    public function getConnections(): array
    {
        return $this->connections;
    }
}
