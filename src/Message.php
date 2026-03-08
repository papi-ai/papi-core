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
 * Immutable value object representing a single message in a conversation.
 *
 * Supports all LLM message roles (system, user, assistant, tool) and multimodal
 * content (text + images). Use the static factory methods for convenient creation.
 */
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
    ) {
    }

    /**
     * Create a user message.
     *
     * @param string|array $content Text string or multimodal content array
     *
     * @return self A new user-role message
     */
    public static function user(string|array $content): self
    {
        return new self(Role::User, $content);
    }

    /**
     * Create a system message containing instructions for the LLM.
     *
     * @param string $content The system prompt text
     *
     * @return self A new system-role message
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
     *
     * @return self A new assistant-role message
     */
    public static function assistant(string $content, ?array $toolCalls = null): self
    {
        return new self(Role::Assistant, $content, $toolCalls);
    }

    /**
     * Create a tool result message to feed a tool's output back into the conversation.
     *
     * @param string $toolCallId The ID of the tool call being responded to
     * @param mixed $result The tool result (will be JSON encoded if not a string)
     *
     * @return self A new tool-role message
     */
    public static function toolResult(string $toolCallId, mixed $result): self
    {
        $content = is_string($result) ? $result : json_encode($result);

        return new self(Role::Tool, $content, toolCallId: $toolCallId);
    }

    /**
     * Create a user message with an image for vision-capable models.
     *
     * @param string $text The text content accompanying the image
     * @param string $imageUrl The image URL (http/https) or base64-encoded data
     * @param string $mediaType The image MIME type (e.g., 'image/jpeg', 'image/png')
     *
     * @return self A new multimodal user-role message
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
     *
     * @return bool True if this message has the system role
     */
    public function isSystem(): bool
    {
        return $this->role === Role::System;
    }

    /**
     * Check if this is a user message.
     *
     * @return bool True if this message has the user role
     */
    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    /**
     * Check if this is an assistant message.
     *
     * @return bool True if this message has the assistant role
     */
    public function isAssistant(): bool
    {
        return $this->role === Role::Assistant;
    }

    /**
     * Check if this is a tool result message.
     *
     * @return bool True if this message has the tool role
     */
    public function isTool(): bool
    {
        return $this->role === Role::Tool;
    }

    /**
     * Check if this message has tool calls.
     *
     * @return bool True if the assistant requested tool invocations in this message
     */
    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== null && count($this->toolCalls) > 0;
    }

    /**
     * Get the text content of this message, extracting from multimodal content if needed.
     *
     * @return string The text content, or empty string if no text is found
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
     * Create a message from a serialised array (e.g., from a conversation store).
     *
     * @param array{role: string, content: string|array, tool_calls?: array, tool_call_id?: string} $data Serialised message data
     *
     * @return self The deserialised message
     */
    public static function fromArray(array $data): self
    {
        $toolCalls = null;
        if (isset($data['tool_calls'])) {
            $toolCalls = array_map(
                fn (array $tc) => new ToolCall($tc['id'], $tc['name'], $tc['arguments']),
                $data['tool_calls'],
            );
        }

        return new self(
            role: Role::from($data['role']),
            content: $data['content'],
            toolCalls: $toolCalls,
            toolCallId: $data['tool_call_id'] ?? null,
        );
    }

    /**
     * Convert to a serialisable array representation.
     *
     * @return array{role: string, content: string|array, tool_calls?: array, tool_call_id?: string}
     */
    public function toArray(): array
    {
        $data = [
            'role' => $this->role->value,
            'content' => $this->content,
        ];

        if ($this->toolCalls !== null) {
            $data['tool_calls'] = array_map(fn (ToolCall $tc) => $tc->toArray(), $this->toolCalls);
        }

        if ($this->toolCallId !== null) {
            $data['tool_call_id'] = $this->toolCallId;
        }

        return $data;
    }
}
