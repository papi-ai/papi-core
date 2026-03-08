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
use RuntimeException;

/**
 * Token bucket rate limiter. No external dependencies.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private float $tokens;

    private float $lastRefill;

    /**
     * @param float $maxTokens Maximum tokens in the bucket
     * @param float $refillRate Tokens added per second
     */
    public function __construct(
        private readonly float $maxTokens = 10.0,
        private readonly float $refillRate = 1.0,
    ) {
        $this->tokens = $this->maxTokens;
        $this->lastRefill = microtime(true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException If the rate limit is exceeded
     */
    public function process(AgentRequest $request, callable $next): Response
    {
        $this->refill();

        if ($this->tokens < 1.0) {
            $waitTime = (1.0 - $this->tokens) / $this->refillRate;

            throw new RuntimeException(
                sprintf('Rate limit exceeded. Try again in %.1f seconds.', $waitTime)
            );
        }

        $this->tokens -= 1.0;

        return $next($request);
    }

    private function refill(): void
    {
        $now = microtime(true);
        $elapsed = $now - $this->lastRefill;
        $this->tokens = min($this->maxTokens, $this->tokens + ($elapsed * $this->refillRate));
        $this->lastRefill = $now;
    }
}
