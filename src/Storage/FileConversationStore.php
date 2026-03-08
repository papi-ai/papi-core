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

namespace PapiAI\Core\Storage;

use PapiAI\Core\Contracts\ConversationStoreInterface;
use PapiAI\Core\Conversation;
use RuntimeException;

/**
 * File-based implementation of ConversationStoreInterface.
 *
 * Persists conversations as JSON files in a directory. Suitable for simple
 * applications and development; for production use, prefer a database-backed store.
 */
final class FileConversationStore implements ConversationStoreInterface
{
    /**
     * @param string $directory Path to the directory where conversation files are stored
     *
     * @throws RuntimeException If the directory cannot be created
     */
    public function __construct(
        private readonly string $directory,
    ) {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true)) {
            throw new RuntimeException("Failed to create conversation store directory: {$this->directory}");
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException If the file cannot be written
     */
    public function save(string $id, Conversation $conversation): void
    {
        $path = $this->path($id);
        $json = json_encode($conversation->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException("Failed to save conversation: {$id}");
        }
    }

    /** {@inheritDoc} */
    public function load(string $id): ?Conversation
    {
        $path = $this->path($id);

        if (!file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);

        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return null;
        }

        return Conversation::fromArray($data);
    }

    /** {@inheritDoc} */
    public function delete(string $id): void
    {
        $path = $this->path($id);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** {@inheritDoc} */
    public function list(int $limit = 50): array
    {
        $files = glob($this->directory . '/*.json');

        if ($files === false) {
            return [];
        }

        // Sort by modification time descending
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        $ids = array_map(
            fn (string $file) => basename($file, '.json'),
            array_slice($files, 0, $limit),
        );

        return $ids;
    }

    private function path(string $id): string
    {
        return $this->directory . '/' . $this->sanitize($id) . '.json';
    }

    private function sanitize(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id) ?? $id;
    }
}
