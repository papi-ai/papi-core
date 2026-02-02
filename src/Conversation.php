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
     * Set the system prompt.
     */
    public function setSystem(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    /**
     * Add a user message.
     */
    public function addUser(string|array $content): self
    {
        $this->messages[] = Message::user($content);
        return $this;
    }

    /**
     * Add an assistant message.
     */
    public function addAssistant(string $content, ?array $toolCalls = null): self
    {
        $this->messages[] = Message::assistant($content, $toolCalls);
        return $this;
    }

    /**
     * Add a tool result.
     */
    public function addToolResult(string $toolCallId, mixed $result): self
    {
        $this->messages[] = Message::toolResult($toolCallId, $result);
        return $this;
    }

    /**
     * Add a message directly.
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
     * Get message count (excluding system).
     */
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * Clear all messages (optionally keep system).
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
     * Get the last message.
     */
    public function getLastMessage(): ?Message
    {
        if (empty($this->messages)) {
            return null;
        }

        return $this->messages[count($this->messages) - 1];
    }

    /**
     * Get the last assistant message.
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
     * Create from array of messages.
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
