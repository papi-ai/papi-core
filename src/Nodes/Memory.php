<?php

namespace Papi\Core\Nodes;

/**
 * Memory Capability Interface
 *
 * Nodes that implement this interface can be used as memory by AI agents.
 * Memory nodes store and retrieve conversation context.
 */
interface Memory
{
    /**
     * Add a message to memory
     */
    public function addMessage(string $role, string $content, array $metadata = []): void;

    /**
     * Get messages from memory
     */
    public function getMessages(?int $limit = null): array;

    /**
     * Clear all messages from memory
     */
    public function clear(): void;

    /**
     * Get context for AI processing
     */
    public function getContext(int $maxTokens = 4000): array;
}
