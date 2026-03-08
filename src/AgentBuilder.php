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

    /**
     * Set the LLM provider for the agent.
     *
     * @param ProviderInterface $provider The provider to use
     *
     * @return self For method chaining
     */
    public function provider(ProviderInterface $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set the model identifier (e.g., 'gpt-4o', 'claude-sonnet-4-20250514').
     *
     * @param string $model The model name
     *
     * @return self For method chaining
     */
    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the system instructions (system prompt) for the agent.
     *
     * @param string $instructions The system prompt text
     *
     * @return self For method chaining
     */
    public function instructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * Add a single tool to the agent.
     *
     * @param ToolInterface $tool The tool to register
     *
     * @return self For method chaining
     */
    public function tool(ToolInterface $tool): self
    {
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * Add multiple tools to the agent at once.
     *
     * @param array<ToolInterface> $tools The tools to register
     *
     * @return self For method chaining
     */
    public function tools(array $tools): self
    {
        $this->tools = array_merge($this->tools, $tools);

        return $this;
    }

    /**
     * Register an event hook (e.g., 'beforeToolCall', 'afterToolCall', 'onError').
     *
     * @param string $name The hook name
     * @param Closure $callback The callback to invoke when the hook fires
     *
     * @return self For method chaining
     */
    public function hook(string $name, Closure $callback): self
    {
        $this->hooks[$name] = $callback;

        return $this;
    }

    /**
     * Set the maximum number of tokens the model can generate.
     *
     * @param int $maxTokens Maximum output tokens
     *
     * @return self For method chaining
     */
    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Set the sampling temperature (higher = more creative, lower = more deterministic).
     *
     * @param float $temperature Temperature value (typically 0.0 to 1.0)
     *
     * @return self For method chaining
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Set the maximum number of agentic turns (tool call loops) before stopping.
     *
     * @param int $maxTurns Maximum iterations of the agentic loop
     *
     * @return self For method chaining
     */
    public function maxTurns(int $maxTurns): self
    {
        $this->maxTurns = $maxTurns;

        return $this;
    }

    /**
     * Add a single middleware to the pipeline.
     *
     * @param MiddlewareInterface $middleware The middleware to add
     *
     * @return self For method chaining
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Add multiple middleware to the pipeline at once.
     *
     * @param array<MiddlewareInterface> $middleware The middleware to add
     *
     * @return self For method chaining
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Build and return the configured Agent instance.
     *
     * @return Agent The fully configured agent
     *
     * @throws InvalidArgumentException If provider or model is not set
     */
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
