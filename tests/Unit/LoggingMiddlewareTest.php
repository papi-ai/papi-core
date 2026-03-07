<?php

declare(strict_types=1);

use PapiAI\Core\AgentRequest;
use PapiAI\Core\Middleware\LoggingMiddleware;
use PapiAI\Core\Response;
use Psr\Log\LoggerInterface;

describe('LoggingMiddleware', function () {
    it('logs request and response', function () {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('log')->twice();

        $middleware = new LoggingMiddleware($logger);
        $request = new AgentRequest(prompt: 'Hello');

        $response = $middleware->process($request, fn () => new Response(text: 'OK'));

        expect($response->text)->toBe('OK');
    });

    it('logs errors on failure', function () {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('log')->once();
        $logger->expects('error')->once();

        $middleware = new LoggingMiddleware($logger);
        $request = new AgentRequest(prompt: 'Hello');

        try {
            $middleware->process($request, function () {
                throw new RuntimeException('Boom');
            });
        } catch (RuntimeException) {
            // expected
        }
    });

    it('uses custom log level', function () {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('log')->with('debug', Mockery::any(), Mockery::any())->twice();

        $middleware = new LoggingMiddleware($logger, level: 'debug');
        $request = new AgentRequest(prompt: 'Hello');

        $middleware->process($request, fn () => new Response(text: 'OK'));
    });
});
