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

namespace PapiAI\Core\Middleware;

use PapiAI\Core\AgentRequest;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Response;

/**
 * Retries failed requests with exponential backoff. No external dependencies.
 */
final class RetryMiddleware implements MiddlewareInterface
{
    /**
     * @param int $maxRetries Maximum number of retries
     * @param int $baseDelayMs Base delay in milliseconds (doubled each retry)
     * @param array<class-string<\Throwable>> $retryOn Exception classes to retry on (empty = all)
     */
    public function __construct(
        private readonly int $maxRetries = 3,
        private readonly int $baseDelayMs = 200,
        private readonly array $retryOn = [],
    ) {
    }

    /** {@inheritDoc} */
    public function process(AgentRequest $request, callable $next): Response
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $next($request);
            } catch (\Throwable $e) {
                $lastException = $e;

                if (!$this->shouldRetry($e) || $attempt === $this->maxRetries) {
                    throw $e;
                }

                $delay = $this->baseDelayMs * (2 ** $attempt);
                usleep($delay * 1000);
            }
        }

        // Unreachable: the loop always runs at least once (attempt=0, maxRetries>=0)
        throw new \LogicException('Retry loop exited unexpectedly');
    }

    private function shouldRetry(\Throwable $e): bool
    {
        if (empty($this->retryOn)) {
            return true;
        }

        foreach ($this->retryOn as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }
}
