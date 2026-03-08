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
 * Conversation history manager.
 *
 * Helps manage message history for multi-turn conversations.
 */
final class Conversation
{
    /** @var array<Message> */
    private array $messages = [];

    private ?string $systemPrompt = null;

    /**
     * Set the system prompt that precedes all other messages.
     *
     * @param string $prompt The system instruction text
     *
     * @return self For method chaining
     */
    public function setSystem(string $prompt): self
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    /**
     * Add a user message to the conversation.
     *
     * @param string|array $content Text string or multimodal content array
     *
     * @return self For method chaining
     */
    public function addUser(string|array $content): self
    {
        $this->messages[] = Message::user($content);

        return $this;
    }

    /**
     * Add an assistant message to the conversation.
     *
     * @param string $content The assistant's text response
     * @param array<ToolCall>|null $toolCalls Tool calls the assistant made, if any
     *
     * @return self For method chaining
     */
    public function addAssistant(string $content, ?array $toolCalls = null): self
    {
        $this->messages[] = Message::assistant($content, $toolCalls);

        return $this;
    }

    /**
     * Add a tool result message to the conversation.
     *
     * @param string $toolCallId The ID of the tool call being responded to
     * @param mixed $result The tool's return value
     *
     * @return self For method chaining
     */
    public function addToolResult(string $toolCallId, mixed $result): self
    {
        $this->messages[] = Message::toolResult($toolCallId, $result);

        return $this;
    }

    /**
     * Add a pre-built message directly to the conversation.
     *
     * @param Message $message The message to add (system messages update the system prompt)
     *
     * @return self For method chaining
     */
    public function addMessage(Message $message): self
    {
        if ($message->isSystem()) {
            $this->systemPrompt = $message->getText();
        } else {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Get all messages including system prompt.
     *
     * @return array<Message>
     */
    public function getMessages(): array
    {
        $result = [];

        if ($this->systemPrompt !== null) {
            $result[] = Message::system($this->systemPrompt);
        }

        return array_merge($result, $this->messages);
    }

    /**
     * Get the number of messages in the conversation (excluding system prompt).
     *
     * @return int Message count
     */
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * Clear all messages from the conversation.
     *
     * @param bool $keepSystem Whether to preserve the system prompt
     *
     * @return self For method chaining
     */
    public function clear(bool $keepSystem = false): self
    {
        $this->messages = [];

        if (!$keepSystem) {
            $this->systemPrompt = null;
        }

        return $this;
    }

    /**
     * Get the most recent message in the conversation.
     *
     * @return Message|null The last message, or null if the conversation is empty
     */
    public function getLastMessage(): ?Message
    {
        if (empty($this->messages)) {
            return null;
        }

        return $this->messages[count($this->messages) - 1];
    }

    /**
     * Get the most recent assistant message in the conversation.
     *
     * @return Message|null The last assistant message, or null if none exists
     */
    public function getLastAssistantMessage(): ?Message
    {
        for ($i = count($this->messages) - 1; $i >= 0; $i--) {
            if ($this->messages[$i]->isAssistant()) {
                return $this->messages[$i];
            }
        }

        return null;
    }

    /**
     * Serialize the conversation to an array for storage.
     *
     * @return array{system: string|null, messages: array<array>}
     */
    public function toArray(): array
    {
        return [
            'system' => $this->systemPrompt,
            'messages' => array_map(fn (Message $m) => $m->toArray(), $this->messages),
        ];
    }

    /**
     * Deserialize a conversation from an array (e.g., from a store).
     *
     * @param array{system?: string|null, messages?: array<array>} $data Serialised conversation data
     *
     * @return self The restored conversation
     */
    public static function fromArray(array $data): self
    {
        $conversation = new self();

        if (isset($data['system']) && $data['system'] !== null) {
            $conversation->setSystem($data['system']);
        }

        foreach ($data['messages'] ?? [] as $messageData) {
            $conversation->messages[] = Message::fromArray($messageData);
        }

        return $conversation;
    }

    /**
     * Create a conversation from an array of Message objects.
     *
     * @param array<Message> $messages Messages to populate the conversation with
     *
     * @return self The new conversation
     */
    public static function fromMessages(array $messages): self
    {
        $conversation = new self();

        foreach ($messages as $message) {
            $conversation->addMessage($message);
        }

        return $conversation;
    }
}
