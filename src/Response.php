<?php

declare(strict_types=1);

namespace PapiAI\Core;

final class Response
{
    /**
     * @param string $text The text response from the LLM
     * @param array|null $data Parsed structured data (if output schema was used)
     * @param array<ToolCall> $toolCalls Tool calls made by the LLM
     * @param array<Message> $messages Full conversation history
     * @param array $usage Token usage statistics
     * @param string|null $stopReason Why the response ended
     */
    public function __construct(
        public readonly string $text,
        public readonly ?array $data = null,
        public readonly array $toolCalls = [],
        public readonly array $messages = [],
        public readonly array $usage = [],
        public readonly ?string $stopReason = null,
    ) {}

    /**
     * Check if the response has tool calls.
     */
    public function hasToolCalls(): bool
    {
        return !empty($this->toolCalls);
    }

    /**
     * Check if the response has structured data.
     */
    public function hasData(): bool
    {
        return $this->data !== null;
    }

    /**
     * Get token usage.
     */
    public function getInputTokens(): int
    {
        return $this->usage['input_tokens'] ?? 0;
    }

    /**
     * Get output token count.
     */
    public function getOutputTokens(): int
    {
        return $this->usage['output_tokens'] ?? 0;
    }

    /**
     * Get total token count.
     */
    public function getTotalTokens(): int
    {
        return $this->getInputTokens() + $this->getOutputTokens();
    }

    /**
     * Create from Anthropic API response.
     */
    public static function fromAnthropic(array $response, array $messages = []): self
    {
        $text = '';
        $toolCalls = [];

        foreach ($response['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = ToolCall::fromAnthropic($block);
            }
        }

        return new self(
            text: $text,
            toolCalls: $toolCalls,
            messages: $messages,
            usage: $response['usage'] ?? [],
            stopReason: $response['stop_reason'] ?? null,
        );
    }

    /**
     * Create from OpenAI API response.
     */
    public static function fromOpenAI(array $response, array $messages = []): self
    {
        $choice = $response['choices'][0] ?? [];
        $message = $choice['message'] ?? [];

        $toolCalls = [];
        foreach ($message['tool_calls'] ?? [] as $tc) {
            $toolCalls[] = ToolCall::fromOpenAI($tc);
        }

        return new self(
            text: $message['content'] ?? '',
            toolCalls: $toolCalls,
            messages: $messages,
            usage: $response['usage'] ?? [],
            stopReason: $choice['finish_reason'] ?? null,
        );
    }

    /**
     * Create a response with structured data.
     */
    public function withData(array $data): self
    {
        return new self(
            text: $this->text,
            data: $data,
            toolCalls: $this->toolCalls,
            messages: $this->messages,
            usage: $this->usage,
            stopReason: $this->stopReason,
        );
    }
}
