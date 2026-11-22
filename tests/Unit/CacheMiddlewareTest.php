<?php

declare(strict_types=1);

use PapiAI\Core\AgentRequest;
use PapiAI\Core\Message;
use PapiAI\Core\Middleware\CacheMiddleware;
use PapiAI\Core\Response;
use Psr\SimpleCache\CacheInterface;

describe('CacheMiddleware', function () {
    it('caches and returns cached responses', function () {
        $cache = Mockery::mock(CacheInterface::class);
        $cache->expects('get')->once()->andReturn(null);
        $cache->expects('set')->once();

        $middleware = new CacheMiddleware($cache, ttl: 60);
        $request = new AgentRequest(prompt: 'Hello');

        $response = $middleware->process($request, fn () => new Response(text: 'OK'));

        expect($response->text)->toBe('OK');
    });

    it('returns cached response on hit', function () {
        $cached = new Response(text: 'Cached');
        $cache = Mockery::mock(CacheInterface::class);
        $cache->expects('get')->once()->andReturn($cached);

        $middleware = new CacheMiddleware($cache);
        $request = new AgentRequest(prompt: 'Hello');

        $called = false;
        $response = $middleware->process($request, function () use (&$called) {
            $called = true;

            return new Response(text: 'Fresh');
        });

        expect($response->text)->toBe('Cached');
        expect($called)->toBeFalse();
    });

    it('keeps two conversations that end on the same message apart', function () {
        $keys = [];
        $cache = Mockery::mock(CacheInterface::class);
        $cache->allows('get')->andReturnUsing(function (string $key) use (&$keys) {
            $keys[] = $key;

            return null;
        });
        $cache->allows('set');
        $middleware = new CacheMiddleware($cache);

        foreach (['I ordered a laptop.', 'I ordered a phone.'] as $earlier) {
            $middleware->process(
                new AgentRequest(prompt: 'Where is it?', messages: [Message::user($earlier), Message::user('Where is it?')]),
                fn () => new Response(text: 'OK'),
            );
        }

        expect($keys[0])->not->toBe($keys[1]);
    });
});
