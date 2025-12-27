<?php

namespace PapiAi\Core;

class Agent
{
    private ?string $provider = null;
    private ?string $model = null;
    private ?string $memoryType = null;
    private array $memoryConfig = [];

    public function __construct()
    {
    }

    public function withModel(string $provider, string $model): self
    {
        $this->provider = $provider;
        $this->model = $model;
        return $this;
    }

    public function withMemory(string $type, array $config = []): self
    {
        $this->memoryType = $type;
        $this->memoryConfig = $config;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getMemoryType(): ?string
    {
        return $this->memoryType;
    }

    public function getMemoryConfig(): array
    {
        return $this->memoryConfig;
    }
}
