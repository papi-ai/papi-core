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
 * Represents the status of a queued agent job.
 */
final class JobStatus
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';

    /**
     * @param string $jobId The unique job identifier
     * @param string $status One of the PENDING/RUNNING/COMPLETED/FAILED constants
     * @param Response|null $result The agent response, available when status is COMPLETED
     * @param string|null $error Error message, available when status is FAILED
     */
    public function __construct(
        public readonly string $jobId,
        public readonly string $status,
        public readonly ?Response $result = null,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * Check if the job is waiting to be picked up by a worker.
     *
     * @return bool True if status is PENDING
     */
    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /**
     * Check if the job is currently being executed.
     *
     * @return bool True if status is RUNNING
     */
    public function isRunning(): bool
    {
        return $this->status === self::RUNNING;
    }

    /**
     * Check if the job finished successfully (result is available).
     *
     * @return bool True if status is COMPLETED
     */
    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    /**
     * Check if the job failed (error message is available).
     *
     * @return bool True if status is FAILED
     */
    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
