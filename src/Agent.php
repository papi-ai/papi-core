<?php

namespace PapiAi\Core;

use ReflectionClass;
use ReflectionMethod;

class Agent
{
    private ?string $provider = null;
    private ?string $model = null;
    protected array $tools = [];
    protected array $schemas = [];

    public function __construct()
    {
    }

    public function withModel(string $provider, string $model): self
    {
        $this->provider = $provider;
        $this->model = $model;
        return $this;
    }

    public function withTools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->tools[] = $tool;
            $this->schemas[] = $this->generateSchema($tool);
        }
        return $this;
    }

    protected function generateSchema(object $tool): array
    {
        $reflection = new ReflectionClass($tool);
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $parameters = [];
            foreach ($method->getParameters() as $param) {
                $parameters[$param->getName()] = [
                    'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                    'required' => !$param->isOptional(),
                ];
            }

            $methods[$method->getName()] = [
                'description' => '', 
                'parameters' => $parameters,
            ];
        }

        return [
            'name' => $reflection->getShortName(),
            'functions' => $methods,
        ];
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }
    
    public function getTools(): array
    {
        return $this->tools;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
