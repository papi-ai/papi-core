<?php

declare(strict_types=1);

use PapiAI\Core\Contracts\EmbeddingProviderInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\EmbeddingResponse;
use PapiAI\Core\FailoverProvider;
use PapiAI\Core\Message;
use PapiAI\Core\Response;
use PapiAI\Core\StreamChunk;

describe('FailoverProvider', function () {
    describe('construction', function () {
        it('requires at least 2 providers', function () {
            $provider = Mockery::mock(ProviderInterface::class);

            expect(fn () => new FailoverProvider([$provider]))
                ->toThrow(InvalidArgumentException::class);
        });

        it('returns failover as name', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->getName())->toBe('failover');
        });
    });

    describe('chat', function () {
        it('uses the first provider when it succeeds', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $expected = new Response(text: 'Hello from p1');
            $p1->expects('chat')->andReturn($expected);
            $p2->shouldNotReceive('chat');

            $failover = new FailoverProvider([$p1, $p2]);
            $result = $failover->chat([Message::user('Hi')]);

            expect($result->text)->toBe('Hello from p1');
            expect($failover->getLastUsedProvider())->toBe($p1);
        });

        it('falls over to second provider on failure', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $p1->expects('chat')->andThrow(new RuntimeException('API down'));
            $p2->expects('chat')->andReturn(new Response(text: 'Hello from p2'));

            $failover = new FailoverProvider([$p1, $p2]);
            $result = $failover->chat([Message::user('Hi')]);

            expect($result->text)->toBe('Hello from p2');
            expect($failover->getLastUsedProvider())->toBe($p2);
        });

        it('throws when all providers fail', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $p1->expects('chat')->andThrow(new RuntimeException('p1 down'));
            $p2->expects('chat')->andThrow(new RuntimeException('p2 down'));

            $failover = new FailoverProvider([$p1, $p2]);

            expect(fn () => $failover->chat([Message::user('Hi')]))
                ->toThrow(RuntimeException::class, 'All providers failed');
        });

        it('only retries on specified exception types', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $p1->expects('chat')->andThrow(new InvalidArgumentException('bad input'));
            $p2->shouldNotReceive('chat');

            $failover = new FailoverProvider([$p1, $p2], retryOn: [RuntimeException::class]);

            expect(fn () => $failover->chat([Message::user('Hi')]))
                ->toThrow(InvalidArgumentException::class);
        });

        it('retries on matching exception types', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $p1->expects('chat')->andThrow(new RuntimeException('timeout'));
            $p2->expects('chat')->andReturn(new Response(text: 'OK'));

            $failover = new FailoverProvider([$p1, $p2], retryOn: [RuntimeException::class]);
            $result = $failover->chat([Message::user('Hi')]);

            expect($result->text)->toBe('OK');
        });
    });

    describe('stream', function () {
        it('returns stream from first successful provider', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $chunks = [new StreamChunk('Hello'), new StreamChunk('', isComplete: true)];
            $p1->expects('stream')->andReturn($chunks);

            $failover = new FailoverProvider([$p1, $p2]);
            $result = iterator_to_array($failover->stream([Message::user('Hi')]));

            expect($result)->toHaveCount(2);
            expect($result[0]->text)->toBe('Hello');
        });

        it('falls over on stream failure', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $p1->expects('stream')->andThrow(new RuntimeException('stream error'));
            $p2->expects('stream')->andReturn([new StreamChunk('Fallback')]);

            $failover = new FailoverProvider([$p1, $p2]);
            $result = iterator_to_array($failover->stream([Message::user('Hi')]));

            expect($result[0]->text)->toBe('Fallback');
        });
    });

    describe('embed', function () {
        it('delegates to first embedding-capable provider', function () {
            $p1 = Mockery::mock(ProviderInterface::class, EmbeddingProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class, EmbeddingProviderInterface::class);

            $expected = new EmbeddingResponse([[0.1, 0.2]], 'model-1');
            $p1->expects('embed')->andReturn($expected);
            $p2->shouldNotReceive('embed');

            $failover = new FailoverProvider([$p1, $p2]);
            $result = $failover->embed('Hello');

            expect($result->first())->toBe([0.1, 0.2]);
        });

        it('throws when no provider supports embeddings', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);
            $p1->expects('getName')->andReturn('p1');
            $p2->expects('getName')->andReturn('p2');

            $failover = new FailoverProvider([$p1, $p2]);

            expect(fn () => $failover->embed('Hello'))
                ->toThrow(RuntimeException::class, 'All providers failed');
        });

        it('falls over to embedding-capable provider', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class, EmbeddingProviderInterface::class);
            $p1->expects('getName')->andReturn('p1');

            $expected = new EmbeddingResponse([[0.5]], 'model-2');
            $p2->expects('embed')->andReturn($expected);

            $failover = new FailoverProvider([$p1, $p2]);
            $result = $failover->embed('Hello');

            expect($result->first())->toBe([0.5]);
        });
    });

    describe('capabilities', function () {
        it('reports tool support if any provider supports it', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);
            $p1->expects('supportsTool')->andReturn(false);
            $p2->expects('supportsTool')->andReturn(true);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->supportsTool())->toBeTrue();
        });

        it('reports no tool support if none support it', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);
            $p1->expects('supportsTool')->andReturn(false);
            $p2->expects('supportsTool')->andReturn(false);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->supportsTool())->toBeFalse();
        });

        it('reports vision support if any provider supports it', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);
            $p1->expects('supportsVision')->andReturn(false);
            $p2->expects('supportsVision')->andReturn(true);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->supportsVision())->toBeTrue();
        });

        it('reports structured output support if any provider supports it', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);
            $p1->expects('supportsStructuredOutput')->andReturn(false);
            $p2->expects('supportsStructuredOutput')->andReturn(true);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->supportsStructuredOutput())->toBeTrue();
        });

        it('returns null for lastUsedProvider before any call', function () {
            $p1 = Mockery::mock(ProviderInterface::class);
            $p2 = Mockery::mock(ProviderInterface::class);

            $failover = new FailoverProvider([$p1, $p2]);

            expect($failover->getLastUsedProvider())->toBeNull();
        });
    });
});
