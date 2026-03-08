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

namespace PapiAI\Core\Exception;

/**
 * Exception thrown when a provider rate limit is exceeded.
 */
class RateLimitException extends ProviderException
{
    /**
     * @param string $provider The provider that rate-limited the request
     * @param int|null $retryAfterSeconds Seconds to wait before retrying, if specified by the provider
     * @param int $statusCode HTTP status code (typically 429)
     * @param array|null $responseBody Raw response body from the provider
     * @param \Throwable|null $previous The underlying exception, if any
     */
    public function __construct(
        string $provider,
        public readonly ?int $retryAfterSeconds = null,
        int $statusCode = 429,
        ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        $message = "Rate limit exceeded for provider: {$provider}";
        if ($retryAfterSeconds !== null) {
            $message .= " (retry after {$retryAfterSeconds}s)";
        }

        parent::__construct($message, $provider, $statusCode, $responseBody, $previous);
    }
}
