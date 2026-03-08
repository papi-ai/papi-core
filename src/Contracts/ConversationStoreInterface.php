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

namespace PapiAI\Core\Contracts;

use PapiAI\Core\Conversation;

/**
 * Contract for persisting and retrieving conversation histories.
 *
 * Enables multi-turn conversations by storing message history between requests.
 * Implementations may use files, databases, or any other storage backend.
 */
interface ConversationStoreInterface
{
    /**
     * Save a conversation.
     *
     * @param string $id Unique conversation identifier
     * @param Conversation $conversation The conversation to persist
     */
    public function save(string $id, Conversation $conversation): void;

    /**
     * Load a conversation by ID.
     *
     * @param string $id Conversation identifier
     * @return Conversation|null Null if not found
     */
    public function load(string $id): ?Conversation;

    /**
     * Delete a conversation by ID.
     *
     * @param string $id Conversation identifier to remove
     */
    public function delete(string $id): void;

    /**
     * List stored conversation IDs.
     *
     * @param int $limit Maximum number to return
     * @return array<string>
     */
    public function list(int $limit = 50): array;
}
