<?php

/*
 * This file is part of PapiAI,
 * A simple but powerful PHP library for building AI agents.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PapiAI\Core;

use Closure;
use InvalidArgumentException;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Contracts\ToolInterface;

/**
 * Fluent builder for creating Agent instances.
 *
 * Usage: Agent::build()->provider($p)->model('gpt-4o')->tools([...])->create()
 */
final class AgentBuilder
{
    private ?ProviderInterface $provider = null;

    private string $model = '';

    private string $instructions = '';

    /** @var array<ToolInterface> */
    private array $tools = [];

    /** @var array<string, Closure> */
    private array $hooks = [];

    private int $maxTokens = 4096;

    private float $temperature = 0.7;

    private int $maxTurns = 10;

    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    public function provider(ProviderInterface $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function instructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function tool(ToolInterface $tool): self
    {
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * @param array<ToolInterface> $tools
     */
    public function tools(array $tools): self
    {
        $this->tools = array_merge($this->tools, $tools);

        return $this;
    }

    public function hook(string $name, Closure $callback): self
    {
        $this->hooks[$name] = $callback;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function maxTurns(int $maxTurns): self
    {
        $this->maxTurns = $maxTurns;

        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @param array<MiddlewareInterface> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function create(): Agent
    {
        if ($this->provider === null) {
            throw new InvalidArgumentException('Provider is required to create an Agent');
        }

        if ($this->model === '') {
            throw new InvalidArgumentException('Model is required to create an Agent');
        }

        return new Agent(
            provider: $this->provider,
            model: $this->model,
            instructions: $this->instructions,
            tools: $this->tools,
            hooks: $this->hooks,
            maxTokens: $this->maxTokens,
            temperature: $this->temperature,
            maxTurns: $this->maxTurns,
            middleware: $this->middleware,
        );
    }
}
