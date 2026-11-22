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

/**
 * Keeps workflow runs for the life of the process, as the arrays a real store would hold.
 */
final class InMemoryWorkflowStore implements WorkflowStoreInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $runs = [];

    public function save(State $state): void
    {
        $this->runs[$state->id] = $state->toArray();
    }

    public function load(string $id): ?State
    {
        return isset($this->runs[$id]) ? State::fromArray($this->runs[$id]) : null;
    }

    public function delete(string $id): void
    {
        unset($this->runs[$id]);
    }
}
