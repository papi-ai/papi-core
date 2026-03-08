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
 * Exception thrown when provider authentication fails.
 */
class AuthenticationException extends ProviderException
{
    /**
     * @param string $provider The provider that rejected authentication
     * @param int $statusCode HTTP status code (typically 401)
     * @param array|null $responseBody Raw response body from the provider
     * @param \Throwable|null $previous The underlying exception, if any
     */
    public function __construct(
        string $provider,
        int $statusCode = 401,
        ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            "Authentication failed for provider: {$provider}",
            $provider,
            $statusCode,
            $responseBody,
            $previous,
        );
    }
}
