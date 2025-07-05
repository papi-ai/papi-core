<?php

namespace Papi\Core;

abstract class Node
{
    protected string $id;
    protected string $name;
    protected array $config = [];
    protected array $input = [];
    protected array $output = [];

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    abstract public function execute(array $input): array;

    public function validate(): bool
    {
        // TODO: Implement node validation logic
        return true;
    }

    public function getInputSchema(): array
    {
        // TODO: Return input schema for validation
        return [];
    }

    public function getOutputSchema(): array
    {
        // TODO: Return output schema for validation
        return [];
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
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
            'type' => static::class,
            'config' => $this->config
        ];
    }
}
