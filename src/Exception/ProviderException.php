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
 * Exception thrown when a provider encounters an error.
 */
class ProviderException extends PapiException
{
    /**
     * @param string $message Human-readable error description
     * @param string $provider The provider name that encountered the error
     * @param int $statusCode HTTP status code from the provider (0 if not applicable)
     * @param array|null $responseBody Raw response body from the provider, if available
     * @param \Throwable|null $previous The underlying exception, if any
     */
    public function __construct(
        string $message,
        public readonly string $provider,
        public readonly int $statusCode = 0,
        public readonly ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
