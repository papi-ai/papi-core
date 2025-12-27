<?php

namespace PapiAi\Core;

class Agent
{
    private array $tools = [];

    public function __construct()
    {
    }

    public function withTools(array $tools): self
    {
        $this->tools = array_merge($this->tools, $tools);
        return $this;
    }

    public function getTools(): array
    {
        return $this->tools;
    }
}
