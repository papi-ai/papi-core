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

use PapiAI\Core\AgentRequest;
use PapiAI\Core\Response;

/**
 * Contract for agent middleware that intercepts requests and responses.
 *
 * Middleware wraps the agent execution pipeline, enabling cross-cutting concerns
 * like logging, caching, rate limiting, and retry logic.
 */
interface MiddlewareInterface
{
    /**
     * Process the agent request through this middleware.
     *
     * @param AgentRequest $request The incoming request
     * @param callable(AgentRequest): Response $next The next handler in the chain
     *
     * @return Response The agent response, potentially modified by this middleware
     */
    public function process(AgentRequest $request, callable $next): Response;
}
