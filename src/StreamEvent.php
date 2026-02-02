<?php

declare(strict_types=1);

namespace PapiAI\Core;

final class StreamEvent
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TOOL_CALL = 'tool_call';
    public const TYPE_TOOL_RESULT = 'tool_result';
    public const TYPE_DONE = 'done';
    public const TYPE_ERROR = 'error';

    public function __construct(
        public readonly string $type,
        public readonly ?string $text = null,
        public readonly ?string $tool = null,
        public readonly ?array $toolInput = null,
        public readonly mixed $result = null,
        public readonly ?string $error = null,
    ) {}

    /**
     * Create a text event.
     */
    public static function text(string $text): self
    {
        return new self(self::TYPE_TEXT, text: $text);
    }

    /**
     * Create a tool call event.
     */
    public static function toolCall(string $tool, array $input): self
    {
        return new self(self::TYPE_TOOL_CALL, tool: $tool, toolInput: $input);
    }

    /**
     * Create a tool result event.
     */
    public static function toolResult(string $tool, mixed $result): self
    {
        return new self(self::TYPE_TOOL_RESULT, tool: $tool, result: $result);
    }

    /**
     * Create a done event.
     */
    public static function done(): self
    {
        return new self(self::TYPE_DONE);
    }

    /**
     * Create an error event.
     */
    public static function error(string $message): self
    {
        return new self(self::TYPE_ERROR, error: $message);
    }
}
