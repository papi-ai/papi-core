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
