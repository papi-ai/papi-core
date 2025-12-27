<?php

namespace PapiAi\Core;

class Agent
{
    private ?string $provider = null;
    private ?string $model = null;

    public function __construct()
    {
    }

    public function withModel(string $provider, string $model): self
    {
        $this->provider = $provider;
        $this->model = $model;
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
}
