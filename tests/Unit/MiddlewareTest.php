<?php

declare(strict_types=1);

use PapiAI\Core\Agent;
use PapiAI\Core\AgentRequest;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Middleware\RateLimitMiddleware;
use PapiAI\Core\Middleware\RetryMiddleware;
use PapiAI\Core\Response;

describe('Middleware', function () {
    beforeEach(function () {
        $this->provider = Mockery::mock(ProviderInterface::class);
        $this->provider->allows('supportsTool')->andReturn(false);
        $this->provider->allows('supportsVision')->andReturn(false);
        $this->provider->allows('supportsStructuredOutput')->andReturn(false);
    });

    describe('Pipeline', function () {
        it('runs request through middleware chain', function () {
            $log = [];

            $middleware1 = new class ($log) implements MiddlewareInterface {
                private array $log;
                public function __construct(array &$log)
                {
                    $this->log = &$log;
                }
                public function process(AgentRequest $request, callable $next): Response
                {
                    $this->log[] = 'before-1';
                    $response = $next($request);
                    $this->log[] = 'after-1';

                    return $response;
                }
            };

            $middleware2 = new class ($log) implements MiddlewareInterface {
                private array $log;
                public function __construct(array &$log)
                {
                    $this->log = &$log;
                }
                public function process(AgentRequest $request, callable $next): Response
                {
                    $this->log[] = 'before-2';
                    $response = $next($request);
                    $this->log[] = 'after-2';

                    return $response;
                }
            };

            $this->provider->expects('chat')->andReturn(
                new Response(text: 'Hello!')
            );

            $agent = new Agent(
                provider: $this->provider,
                model: 'test',
                middleware: [$middleware1, $middleware2],
            );

            $response = $agent->run('Hi');

            expect($response->text)->toBe('Hello!');
            expect($log)->toBe(['before-1', 'before-2', 'after-2', 'after-1']);
        });

        it('works without middleware', function () {
            $this->provider->expects('chat')->andReturn(
                new Response(text: 'Hello!')
            );

            $agent = new Agent(
                provider: $this->provider,
                model: 'test',
            );

            $response = $agent->run('Hi');

            expect($response->text)->toBe('Hello!');
        });

        it('allows middleware to modify the request', function () {
            $middleware = new class () implements MiddlewareInterface {
                public function process(AgentRequest $request, callable $next): Response
                {
                    $modified = new AgentRequest(
                        prompt: $request->prompt . ' (modified)',
                        options: $request->options,
                    );

                    return $next($modified);
                }
            };

            $this->provider->expects('chat')->withArgs(function (array $messages) {
                return $messages[0]->getText() === 'Hi (modified)';
            })->andReturn(new Response(text: 'OK'));

            $agent = new Agent(
                provider: $this->provider,
                model: 'test',
                middleware: [$middleware],
            );

            $agent->run('Hi');
        });

        it('supports addMiddleware method', function () {
            $called = false;
            $middleware = new class ($called) implements MiddlewareInterface {
                private bool $called;
                public function __construct(bool &$called)
                {
                    $this->called = &$called;
                }
                public function process(AgentRequest $request, callable $next): Response
                {
                    $this->called = true;

                    return $next($request);
                }
            };

            $this->provider->expects('chat')->andReturn(new Response(text: 'OK'));

            $agent = new Agent(provider: $this->provider, model: 'test');
            $agent->addMiddleware($middleware);
            $agent->run('Hi');

            expect($called)->toBeTrue();
        });
    });

    describe('RateLimitMiddleware', function () {
        it('allows requests within limit', function () {
            $middleware = new RateLimitMiddleware(maxTokens: 5.0, refillRate: 1.0);
            $request = new AgentRequest(prompt: 'Hello');

            $response = $middleware->process($request, fn () => new Response(text: 'OK'));

            expect($response->text)->toBe('OK');
        });

        it('throws when rate limit exceeded', function () {
            $middleware = new RateLimitMiddleware(maxTokens: 1.0, refillRate: 0.001);
            $request = new AgentRequest(prompt: 'Hello');

            // First request succeeds
            $middleware->process($request, fn () => new Response(text: 'OK'));

            // Second should fail
            $middleware->process($request, fn () => new Response(text: 'OK'));
        })->throws(RuntimeException::class, 'Rate limit exceeded');
    });

    describe('RetryMiddleware', function () {
        it('returns on first success', function () {
            $middleware = new RetryMiddleware(maxRetries: 3, baseDelayMs: 1);
            $request = new AgentRequest(prompt: 'Hello');

            $response = $middleware->process($request, fn () => new Response(text: 'OK'));

            expect($response->text)->toBe('OK');
        });

        it('retries on failure and succeeds', function () {
            $middleware = new RetryMiddleware(maxRetries: 3, baseDelayMs: 1);
            $request = new AgentRequest(prompt: 'Hello');

            $attempts = 0;
            $response = $middleware->process($request, function () use (&$attempts) {
                $attempts++;
                if ($attempts < 3) {
                    throw new RuntimeException('Temporary error');
                }

                return new Response(text: 'OK');
            });

            expect($response->text)->toBe('OK');
            expect($attempts)->toBe(3);
        });

        it('throws after max retries exhausted', function () {
            $middleware = new RetryMiddleware(maxRetries: 2, baseDelayMs: 1);
            $request = new AgentRequest(prompt: 'Hello');

            $middleware->process($request, function () {
                throw new RuntimeException('Persistent error');
            });
        })->throws(RuntimeException::class, 'Persistent error');

        it('only retries specified exception classes', function () {
            $middleware = new RetryMiddleware(
                maxRetries: 3,
                baseDelayMs: 1,
                retryOn: [RuntimeException::class],
            );
            $request = new AgentRequest(prompt: 'Hello');

            $middleware->process($request, function () {
                throw new InvalidArgumentException('Wrong type');
            });
        })->throws(InvalidArgumentException::class, 'Wrong type');
    });
});
