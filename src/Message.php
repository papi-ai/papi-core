<?php

declare(strict_types=1);

namespace PapiAI\Core;

final class Message
{
    /**
     * @param Role $role The message role
     * @param string|array $content The message content (string or array for multimodal)
     * @param array<ToolCall>|null $toolCalls Tool calls made by the assistant
     * @param string|null $toolCallId The tool call ID this message is responding to
     */
    public function __construct(
        public readonly Role $role,
        public readonly string|array $content,
        public readonly ?array $toolCalls = null,
        public readonly ?string $toolCallId = null,
    ) {}

    /**
     * Create a user message.
     */
    public static function user(string|array $content): self
    {
        return new self(Role::User, $content);
    }

    /**
     * Create a system message.
     */
    public static function system(string $content): self
    {
        return new self(Role::System, $content);
    }

    /**
     * Create an assistant message.
     *
     * @param string $content The message content
     * @param array<ToolCall>|null $toolCalls Tool calls made by the assistant
     */
    public static function assistant(string $content, ?array $toolCalls = null): self
    {
        return new self(Role::Assistant, $content, $toolCalls);
    }

    /**
     * Create a tool result message.
     *
     * @param string $toolCallId The ID of the tool call being responded to
     * @param mixed $result The tool result (will be JSON encoded if not a string)
     */
    public static function toolResult(string $toolCallId, mixed $result): self
    {
        $content = is_string($result) ? $result : json_encode($result);

        return new self(Role::Tool, $content, toolCallId: $toolCallId);
    }

    /**
     * Create a user message with an image.
     *
     * @param string $text The text content
     * @param string $imageUrl The image URL or base64 data
     * @param string $mediaType The image media type (e.g., 'image/jpeg')
     */
    public static function userWithImage(string $text, string $imageUrl, string $mediaType = 'image/jpeg'): self
    {
        // Check if it's a URL or base64
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            $content = [
                ['type' => 'text', 'text' => $text],
                ['type' => 'image', 'source' => ['type' => 'url', 'url' => $imageUrl]],
            ];
        } else {
            $content = [
                ['type' => 'text', 'text' => $text],
                ['type' => 'image', 'source' => [
                    'type' => 'base64',
                    'media_type' => $mediaType,
                    'data' => $imageUrl,
                ]],
            ];
        }

        return new self(Role::User, $content);
    }

    /**
     * Check if this is a system message.
     */
    public function isSystem(): bool
    {
        return $this->role === Role::System;
    }

    /**
     * Check if this is a user message.
     */
    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistant(): bool
    {
        return $this->role === Role::Assistant;
    }

    /**
     * Check if this is a tool result message.
     */
    public function isTool(): bool
    {
        return $this->role === Role::Tool;
    }

    /**
     * Check if this message has tool calls.
     */
    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== null && count($this->toolCalls) > 0;
    }

    /**
     * Get the text content of this message.
     */
    public function getText(): string
    {
        if (is_string($this->content)) {
            return $this->content;
        }

        // Extract text from multimodal content
        foreach ($this->content as $part) {
            if (isset($part['type']) && $part['type'] === 'text') {
                return $part['text'];
            }
        }

        return '';
    }

    /**
     * Convert to array format.
     */
    public function toArray(): array
    {
        $data = [
            'role' => $this->role->value,
            'content' => $this->content,
        ];

        if ($this->toolCalls !== null) {
            $data['tool_calls'] = array_map(fn(ToolCall $tc) => $tc->toArray(), $this->toolCalls);
        }

        if ($this->toolCallId !== null) {
            $data['tool_call_id'] = $this->toolCallId;
        }

        return $data;
    }
}
