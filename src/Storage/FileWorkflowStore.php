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

use PapiAI\Core\Contracts\WorkflowStoreInterface;
use PapiAI\Core\Workflow\State;
use RuntimeException;

/**
 * Keeps each workflow run as one JSON file. A save replaces the file in one move, so a reader
 * never sees half a run.
 */
final class FileWorkflowStore implements WorkflowStoreInterface
{
    public function __construct(
        private readonly string $directory,
    ) {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Failed to create the workflow directory "%s".', $this->directory));
        }
    }

    public function save(State $state): void
    {
        $path = $this->pathOf($state->id);
        $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $json = json_encode($state->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($temporary, $json) === false || !rename($temporary, $path)) {
            throw new RuntimeException(sprintf('Failed to save the workflow run "%s".', $state->id));
        }
    }

    public function load(string $id): ?State
    {
        $path = $this->pathOf($id);
        $json = is_file($path) ? file_get_contents($path) : false;

        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('The stored workflow run "%s" is not a JSON object.', $id));
        }

        return State::fromArray($data);
    }

    public function delete(string $id): void
    {
        $path = $this->pathOf($id);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * IDs keep their letters, digits, "_" and "-"; a hash of the whole ID keeps two that differ
     * only in other characters apart.
     */
    private function pathOf(string $id): string
    {
        $safe = (string) preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);

        if ($safe !== $id) {
            $safe .= '-' . substr(hash('sha256', $id), 0, 8);
        }

        return $this->directory . '/' . $safe . '.json';
    }
}
