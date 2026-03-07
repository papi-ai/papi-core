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
use Psr\SimpleCache\CacheInterface;

/**
 * Caches agent responses using a PSR-16 simple cache.
 *
 * Requires psr/simple-cache to be installed (suggested, not required).
 */
final class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600,
        private readonly string $prefix = 'papi:',
    ) {
    }

    public function process(AgentRequest $request, callable $next): Response
    {
        $key = $this->buildKey($request);

        $cached = $this->cache->get($key);

        if ($cached instanceof Response) {
            return $cached;
        }

        $response = $next($request);

        $this->cache->set($key, $response, $this->ttl);

        return $response;
    }

    private function buildKey(AgentRequest $request): string
    {
        $data = $request->prompt . json_encode($request->options);

        return $this->prefix . md5($data);
    }
}
