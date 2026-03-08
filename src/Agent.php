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
use PapiAI\Core\Contracts\AgentInterface;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Contracts\ToolInterface;
use PapiAI\Core\Schema\Schema;

/**
 * Core agent implementation that orchestrates LLM interactions and tool execution.
 *
 * Manages the agentic loop: sending prompts to the provider, executing tool calls,
 * feeding results back, and repeating until the model produces a final response
 * or the maximum number of turns is reached. Supports middleware pipelines,
 * event hooks, structured output, and streaming.
 *
 * Create instances via the fluent builder: Agent::build()->provider($p)->model('...')->create()
 */
final class Agent implements AgentInterface
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @var array<string, Closure> */
    private array $hooks = [];

    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    /**
     * @param ProviderInterface $provider The LLM provider
     * @param string $model The model to use
     * @param string $instructions System instructions
     * @param array<ToolInterface> $tools Available tools
     * @param array<string, Closure> $hooks Event hooks
     * @param int $maxTokens Max tokens in response
     * @param float $temperature Temperature for generation
     * @param int $maxTurns Max agentic turns (tool call loops)
     * @param array<MiddlewareInterface> $middleware Middleware pipeline
     */
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly string $model,
        private readonly string $instructions = '',
        array $tools = [],
        array $hooks = [],
        private readonly int $maxTokens = 4096,
        private readonly float $temperature = 0.7,
        private readonly int $maxTurns = 10,
        array $middleware = [],
    ) {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }
        $this->hooks = $hooks;
        $this->middleware = $middleware;
    }

    /**
     * Create a fluent builder for constructing an Agent.
     *
     * @return AgentBuilder A new builder instance
     */
    public static function build(): AgentBuilder
    {
        return new AgentBuilder();
    }

    /** {@inheritDoc} */
    public function addTool(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;

        return $this;
    }

    /** {@inheritDoc} */
    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * Add middleware to the agent's request/response pipeline.
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
     * Run the agent with a prompt, executing the full agentic loop.
     *
     * Sends the prompt through any middleware, then iterates: call the LLM,
     * execute tool calls, feed results back, until a final response or max turns.
     *
     * @param string $prompt The user prompt
     * @param array{
     *     outputSchema?: Schema,
     *     context?: mixed,
     *     maxTurns?: int,
     * } $options Run options
     *
     * @return Response The final agent response
     *
     * @throws InvalidArgumentException If max turns is exceeded or structured output parsing fails
     */
    public function run(string $prompt, array $options = []): Response
    {
        if (!empty($this->middleware)) {
            $request = new AgentRequest(prompt: $prompt, options: $options);

            return $this->runThroughMiddleware($request);
        }

        return $this->executeRun($prompt, $options);
    }

    /**
     * Run the request through the middleware pipeline.
     */
    private function runThroughMiddleware(AgentRequest $request): Response
    {
        $handler = fn (AgentRequest $req): Response => $this->executeRun($req->prompt, $req->options);

        // Build the middleware chain from inside out
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $handler;
            $handler = fn (AgentRequest $req): Response => $middleware->process($req, $next);
        }

        return $handler($request);
    }

    /**
     * Execute the actual agent run (the inner handler).
     */
    private function executeRun(string $prompt, array $options = []): Response
    {
        $messages = [];
        $maxTurns = $options['maxTurns'] ?? $this->maxTurns;
        $context = $options['context'] ?? null;
        $outputSchema = $options['outputSchema'] ?? null;

        // Add system message
        if ($this->instructions !== '') {
            $messages[] = Message::system($this->instructions);
        }

        // Add user message
        $messages[] = Message::user($prompt);

        // Agentic loop
        for ($turn = 0; $turn < $maxTurns; $turn++) {
            $response = $this->callProvider($messages, $outputSchema);

            // Add assistant message to history
            $messages[] = Message::assistant($response->text, $response->toolCalls ?: null);

            // If no tool calls, we're done
            if (!$response->hasToolCalls()) {
                return $this->finalizeResponse($response, $messages, $outputSchema);
            }

            // Execute tool calls
            foreach ($response->toolCalls as $toolCall) {
                $result = $this->executeTool($toolCall, $context);
                $messages[] = Message::toolResult($toolCall->id, $result);
            }
        }

        // Max turns reached
        throw new InvalidArgumentException("Agent reached maximum turns ({$maxTurns}) without completing");
    }

    /**
     * {@inheritDoc}
     *
     * Streams text chunks from the provider without executing the agentic tool loop.
     */
    public function stream(string $prompt, array $options = []): iterable
    {
        $messages = [];

        if ($this->instructions !== '') {
            $messages[] = Message::system($this->instructions);
        }
        $messages[] = Message::user($prompt);

        foreach ($this->provider->stream($messages, $this->getProviderOptions()) as $chunk) {
            yield $chunk;
        }
    }

    /**
     * {@inheritDoc}
     *
     * Streams detailed events including text, tool calls, tool results, and completion.
     */
    public function streamEvents(string $prompt, array $options = []): iterable
    {
        $messages = [];
        $maxTurns = $options['maxTurns'] ?? $this->maxTurns;
        $context = $options['context'] ?? null;

        if ($this->instructions !== '') {
            $messages[] = Message::system($this->instructions);
        }
        $messages[] = Message::user($prompt);

        for ($turn = 0; $turn < $maxTurns; $turn++) {
            // Stream the response
            foreach ($this->provider->stream($messages, $this->getProviderOptions()) as $chunk) {
                if ($chunk->text !== '') {
                    yield StreamEvent::text($chunk->text);
                }
            }

            // Get the complete response to check for tool calls
            $response = $this->callProvider($messages);
            $messages[] = Message::assistant($response->text, $response->toolCalls ?: null);

            if (!$response->hasToolCalls()) {
                yield StreamEvent::done();

                return;
            }

            // Execute tool calls
            foreach ($response->toolCalls as $toolCall) {
                yield StreamEvent::toolCall($toolCall->name, $toolCall->arguments);

                $result = $this->executeTool($toolCall, $context);
                $messages[] = Message::toolResult($toolCall->id, $result);

                yield StreamEvent::toolResult($toolCall->name, $result);
            }
        }

        yield StreamEvent::error("Max turns ({$maxTurns}) reached");
    }

    /**
     * Call the provider with current messages.
     */
    private function callProvider(array $messages, ?Schema $outputSchema = null): Response
    {
        $options = $this->getProviderOptions();

        if ($outputSchema !== null && $this->provider->supportsStructuredOutput()) {
            $options['outputSchema'] = $outputSchema->toJsonSchema();
        }

        return $this->provider->chat($messages, $options);
    }

    /**
     * Get provider options.
     */
    private function getProviderOptions(): array
    {
        $options = [
            'model' => $this->model,
            'maxTokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        if (!empty($this->tools)) {
            $options['tools'] = array_values(array_map(
                fn (ToolInterface $tool) => $tool->toAnthropic(),
                $this->tools
            ));
        }

        return $options;
    }

    /**
     * Execute a tool call.
     */
    private function executeTool(ToolCall $toolCall, mixed $context = null): mixed
    {
        $tool = $this->tools[$toolCall->name] ?? null;

        if ($tool === null) {
            return ['error' => "Unknown tool: {$toolCall->name}"];
        }

        // Before hook
        if (isset($this->hooks['beforeToolCall'])) {
            ($this->hooks['beforeToolCall'])($toolCall->name, $toolCall->arguments);
        }

        $startTime = microtime(true);

        try {
            $result = $tool->execute($toolCall->arguments, $context);
        } catch (\Throwable $e) {
            if (isset($this->hooks['onError'])) {
                ($this->hooks['onError'])($e);
            }

            return ['error' => $e->getMessage()];
        }

        $duration = microtime(true) - $startTime;

        // After hook
        if (isset($this->hooks['afterToolCall'])) {
            ($this->hooks['afterToolCall'])($toolCall->name, $result, $duration);
        }

        return $result;
    }

    /**
     * Finalize the response, parsing structured output if needed.
     */
    private function finalizeResponse(Response $response, array $messages, ?Schema $outputSchema): Response
    {
        if ($outputSchema === null) {
            return new Response(
                text: $response->text,
                toolCalls: $response->toolCalls,
                messages: $messages,
                usage: $response->usage,
                stopReason: $response->stopReason,
            );
        }

        // Try to parse JSON from the response
        $text = trim($response->text);

        // Remove markdown code blocks if present
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
        } elseif (str_starts_with($text, '```')) {
            $text = substr($text, 3);
        }
        if (str_ends_with($text, '```')) {
            $text = substr($text, 0, -3);
        }
        $text = trim($text);

        $data = json_decode($text, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                'Failed to parse structured output: ' . json_last_error_msg()
            );
        }

        // Validate against schema
        $outputSchema->parse($data);

        return new Response(
            text: $response->text,
            data: $data,
            toolCalls: $response->toolCalls,
            messages: $messages,
            usage: $response->usage,
            stopReason: $response->stopReason,
        );
    }
}
