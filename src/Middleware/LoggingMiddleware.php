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
use Psr\Log\LoggerInterface;

/**
 * Logs agent requests and responses using a PSR-3 logger.
 *
 * Requires psr/log to be installed (suggested, not required).
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $level = 'info',
    ) {
    }

    public function process(AgentRequest $request, callable $next): Response
    {
        $startTime = microtime(true);

        $this->logger->log($this->level, 'Agent request', [
            'prompt' => $request->prompt,
            'options' => $request->options,
        ]);

        try {
            $response = $next($request);

            $duration = microtime(true) - $startTime;

            $this->logger->log($this->level, 'Agent response', [
                'duration' => round($duration, 4),
                'tokens' => $response->getTotalTokens(),
                'stop_reason' => $response->stopReason,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Agent error', [
                'duration' => round($duration, 4),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
