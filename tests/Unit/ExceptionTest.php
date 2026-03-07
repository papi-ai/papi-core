<?php

declare(strict_types=1);

use PapiAI\Core\Exception\AuthenticationException;
use PapiAI\Core\Exception\PapiException;
use PapiAI\Core\Exception\ProviderException;
use PapiAI\Core\Exception\RateLimitException;

describe('Exception hierarchy', function () {
    it('PapiException extends RuntimeException', function () {
        $e = new PapiException('test');

        expect($e)->toBeInstanceOf(RuntimeException::class);
        expect($e->getMessage())->toBe('test');
    });

    it('ProviderException extends PapiException', function () {
        $e = new ProviderException(
            message: 'Server error',
            provider: 'openai',
            statusCode: 500,
            responseBody: ['error' => 'internal'],
        );

        expect($e)->toBeInstanceOf(PapiException::class);
        expect($e->provider)->toBe('openai');
        expect($e->statusCode)->toBe(500);
        expect($e->responseBody)->toBe(['error' => 'internal']);
        expect($e->getCode())->toBe(500);
    });

    it('RateLimitException extends ProviderException', function () {
        $e = new RateLimitException(
            provider: 'anthropic',
            retryAfterSeconds: 30,
        );

        expect($e)->toBeInstanceOf(ProviderException::class);
        expect($e->provider)->toBe('anthropic');
        expect($e->retryAfterSeconds)->toBe(30);
        expect($e->statusCode)->toBe(429);
        expect($e->getMessage())->toContain('Rate limit exceeded');
        expect($e->getMessage())->toContain('retry after 30s');
    });

    it('RateLimitException without retry-after', function () {
        $e = new RateLimitException(provider: 'openai');

        expect($e->retryAfterSeconds)->toBeNull();
        expect($e->getMessage())->not->toContain('retry after');
    });

    it('AuthenticationException extends ProviderException', function () {
        $e = new AuthenticationException(provider: 'google');

        expect($e)->toBeInstanceOf(ProviderException::class);
        expect($e->provider)->toBe('google');
        expect($e->statusCode)->toBe(401);
        expect($e->getMessage())->toContain('Authentication failed');
        expect($e->getMessage())->toContain('google');
    });
});
