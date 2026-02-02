<?php

declare(strict_types=1);

namespace PapiAI\Core;

use Closure;
use InvalidArgumentException;
use PapiAI\Core\Contracts\AgentInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Contracts\ToolInterface;
use PapiAI\Core\Schema\Schema;

final class Agent implements AgentInterface
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @var array<string, Closure> */
    private array $hooks = [];

    /**
     * @param ProviderInterface $provider The LLM provider
     * @param string $model The model to use
     * @param string $instructions System instructions
     * @param array<ToolInterface> $tools Available tools
     * @param array<string, Closure> $hooks Event hooks
     * @param int $maxTokens Max tokens in response
     * @param float $temperature Temperature for generation
     * @param int $maxTurns Max agentic turns (tool call loops)
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
    ) {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }
        $this->hooks = $hooks;
    }

    public function addTool(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * Run the agent with a prompt.
     *
     * @param string $prompt The user prompt
     * @param array{
     *     outputSchema?: Schema,
     *     context?: mixed,
     *     maxTurns?: int,
     * } $options Run options
     */
    public function run(string $prompt, array $options = []): Response
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
     * Stream the agent response as text chunks.
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
     * Stream the agent response with detailed events.
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
            $fullText = '';
            $toolCalls = [];

            // Stream the response
            foreach ($this->provider->stream($messages, $this->getProviderOptions()) as $chunk) {
                if ($chunk->text !== '') {
                    $fullText .= $chunk->text;
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
                fn(ToolInterface $tool) => $tool->toAnthropic(),
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
                "Failed to parse structured output: " . json_last_error_msg()
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
