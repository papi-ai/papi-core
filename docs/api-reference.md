# API Reference

## Core Classes

### Workflow
- `__construct(string $name)`
- `addNode(Node $node): self`
- `addConnection(Connection $connection): self`
- `execute(array $input = []): Execution`
- `validate(): bool`
- `toArray(): array`
- `fromArray(array $data): self`
- `getName(): string`
- `getNodes(): array`
- `getConnections(): array`

### Node (abstract)
- `__construct(string $id, string $name)`
- `execute(array $input): array`
- `setConfig(array $config): self`
- `getConfig(): array`
- `getId(): string`
- `getName(): string`
- `toArray(): array`

### Connection
- `__construct(string $sourceNode, string $targetNode, string $sourceOutput = 'output', string $targetInput = 'input')`
- `getId(): string`
- `getSourceNode(): string`
- `getTargetNode(): string`
- `getSourceOutput(): string`
- `getTargetInput(): string`
- `setTransform(array $transform): self`
- `getTransform(): array`
- `toArray(): array`

### Execution
- `__construct(string $workflowId, string $status, array $inputData, array $nodeResults = [])`
- `getId(): string`
- `getWorkflowId(): string`
- `getStatus(): string`
- `getInputData(): array`
- `getOutputData(): array`
- `setOutputData(array $outputData): self`
- `getNodeResults(): array`
- `addNodeResult(string $nodeId, array $result): self`
- `getErrorMessage(): ?string`
- `setErrorMessage(?string $errorMessage): self`
- `getStartedAt(): float`
- `getCompletedAt(): ?float`
- `complete(): self`
- `getDuration(): float`
- `getOutput(): array`
- `toArray(): array`

---

**< Previous**: [Templates](./templates.md) | **Home**: [Documentation Index](./index.md) | **Next >**: [Troubleshooting](./troubleshooting.md) 