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

use PapiAI\Core\Workflow\State;

/**
 * Keeps workflow runs between processes, so a paused or failed run can be resumed later.
 */
interface WorkflowStoreInterface
{
    /**
     * Save the run under its own ID, replacing what was stored.
     */
    public function save(State $state): void;

    /**
     * @return State|null The run, or null when none is stored under that ID
     */
    public function load(string $id): ?State;

    public function delete(string $id): void;
}
