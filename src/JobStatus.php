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

    public function __construct(
        public readonly string $jobId,
        public readonly string $status,
        public readonly ?Response $result = null,
        public readonly ?string $error = null,
    ) {
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isRunning(): bool
    {
        return $this->status === self::RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
