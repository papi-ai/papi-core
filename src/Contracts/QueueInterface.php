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

use PapiAI\Core\AgentJob;
use PapiAI\Core\JobStatus;

/**
 * Contract for asynchronous agent job queues.
 *
 * Enables dispatching agent work to background workers and polling for results,
 * useful for long-running or high-volume agent executions.
 */
interface QueueInterface
{
    /**
     * Dispatch an agent job to the queue.
     *
     * @return string The job ID
     */
    public function dispatch(AgentJob $job): string;

    /**
     * Get the status of a queued job.
     *
     * @param string $jobId The job identifier returned by dispatch()
     *
     * @return JobStatus Current status including result when completed
     */
    public function status(string $jobId): JobStatus;
}
