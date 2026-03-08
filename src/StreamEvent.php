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

/**
 * Immutable value object representing a detailed event during agent streaming.
 *
 * Stream events provide granular visibility into the agent loop: text generation,
 * tool calls, tool results, completion, and errors. Used by streamEvents() to
 * give callers full control over how each phase is rendered.
 */
final class StreamEvent
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TOOL_CALL = 'tool_call';
    public const TYPE_TOOL_RESULT = 'tool_result';
    public const TYPE_DONE = 'done';
    public const TYPE_ERROR = 'error';

    /**
     * @param string $type One of the TYPE_* constants identifying the event kind
     * @param string|null $text Text content (for TYPE_TEXT events)
     * @param string|null $tool Tool name (for TYPE_TOOL_CALL and TYPE_TOOL_RESULT events)
     * @param array<string, mixed>|null $toolInput Tool arguments (for TYPE_TOOL_CALL events)
     * @param mixed $result Tool execution result (for TYPE_TOOL_RESULT events)
     * @param string|null $error Error message (for TYPE_ERROR events)
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $text = null,
        public readonly ?string $tool = null,
        public readonly ?array $toolInput = null,
        public readonly mixed $result = null,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * Create a text event for a chunk of generated content.
     *
     * @param string $text The text chunk
     *
     * @return self A TYPE_TEXT event
     */
    public static function text(string $text): self
    {
        return new self(self::TYPE_TEXT, text: $text);
    }

    /**
     * Create an event indicating the agent is invoking a tool.
     *
     * @param string $tool The tool name being called
     * @param array<string, mixed> $input The arguments passed to the tool
     *
     * @return self A TYPE_TOOL_CALL event
     */
    public static function toolCall(string $tool, array $input): self
    {
        return new self(self::TYPE_TOOL_CALL, tool: $tool, toolInput: $input);
    }

    /**
     * Create an event carrying the result of a tool execution.
     *
     * @param string $tool The tool name that produced the result
     * @param mixed $result The tool's return value
     *
     * @return self A TYPE_TOOL_RESULT event
     */
    public static function toolResult(string $tool, mixed $result): self
    {
        return new self(self::TYPE_TOOL_RESULT, tool: $tool, result: $result);
    }

    /**
     * Create an event signalling the agent has finished successfully.
     *
     * @return self A TYPE_DONE event
     */
    public static function done(): self
    {
        return new self(self::TYPE_DONE);
    }

    /**
     * Create an event indicating an error occurred during streaming.
     *
     * @param string $message The error description
     *
     * @return self A TYPE_ERROR event
     */
    public static function error(string $message): self
    {
        return new self(self::TYPE_ERROR, error: $message);
    }
}
