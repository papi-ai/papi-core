<?php

declare(strict_types=1);

use PapiAI\Core\Response;
use PapiAI\Core\Usage;

describe('Usage', function () {
    it('stores typed token counts and derives the total', function () {
        $usage = new Usage(inputTokens: 100, outputTokens: 20);

        expect($usage->inputTokens)->toBe(100);
        expect($usage->outputTokens)->toBe(20);
        expect($usage->totalTokens)->toBe(120);
    });

    it('respects an explicit total when given', function () {
        $usage = new Usage(inputTokens: 100, outputTokens: 20, totalTokens: 130);

        expect($usage->totalTokens)->toBe(130);
    });

    it('normalises Anthropic-style keys', function () {
        $usage = Usage::fromArray(['input_tokens' => 100, 'output_tokens' => 20]);

        expect($usage->inputTokens)->toBe(100);
        expect($usage->outputTokens)->toBe(20);
    });

    it('normalises OpenAI-style keys', function () {
        $usage = Usage::fromArray(['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120]);

        expect($usage->inputTokens)->toBe(100);
        expect($usage->outputTokens)->toBe(20);
        expect($usage->totalTokens)->toBe(120);
    });

    it('preserves the raw payload', function () {
        $usage = Usage::fromArray(['prompt_tokens' => 5, 'extra' => 'kept']);

        expect($usage->toArray())->toBe(['prompt_tokens' => 5, 'extra' => 'kept']);
    });

    describe('legacy array-access (backward compatibility)', function () {
        it('reads legacy keys through both naming styles', function () {
            $usage = Usage::fromArray(['input_tokens' => 100, 'output_tokens' => 20]);

            expect($usage['input_tokens'])->toBe(100);
            expect($usage['prompt_tokens'])->toBe(100);
            expect($usage['output_tokens'])->toBe(20);
            expect($usage['completion_tokens'])->toBe(20);
            expect($usage['total_tokens'])->toBe(120);
        });

        it('reports offset existence for known and raw keys', function () {
            $usage = Usage::fromArray(['input_tokens' => 1, 'custom' => 'x']);

            expect(isset($usage['input_tokens']))->toBeTrue();
            expect(isset($usage['custom']))->toBeTrue();
            expect(isset($usage['missing']))->toBeFalse();
        });

        it('is immutable', function () {
            $usage = new Usage(1, 2);

            expect(fn () => $usage['input_tokens'] = 5)->toThrow(LogicException::class);
        });
    });

    describe('Response integration', function () {
        it('accepts a raw array and keeps the getters working (Anthropic keys)', function () {
            $response = new Response(text: 'hi', usage: ['input_tokens' => 100, 'output_tokens' => 20]);

            expect($response->getInputTokens())->toBe(100);
            expect($response->getOutputTokens())->toBe(20);
            expect($response->getTotalTokens())->toBe(120);
            // legacy array access still resolves
            expect($response->usage['input_tokens'])->toBe(100);
        });

        it('fixes the token counts for OpenAI-style usage (previously returned 0)', function () {
            $response = new Response(
                text: 'hi',
                usage: ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
            );

            expect($response->getInputTokens())->toBe(100);
            expect($response->getOutputTokens())->toBe(20);
            expect($response->getTotalTokens())->toBe(120);
        });

        it('accepts a Usage object directly', function () {
            $response = new Response(text: 'hi', usage: new Usage(7, 3));

            expect($response->getInputTokens())->toBe(7);
            expect($response->usage)->toBeInstanceOf(Usage::class);
        });
    });

    describe('adding up', function () {
        it('adds the tokens of two usages into a new one', function () {
            $first = new Usage(100, 20);
            $total = $first->plus(Usage::fromArray(['prompt_tokens' => 50, 'completion_tokens' => 5, 'total_tokens' => 60]));

            expect($total->inputTokens)->toBe(150);
            expect($total->outputTokens)->toBe(25);
            expect($total->totalTokens)->toBe(180);
            expect($first->inputTokens)->toBe(100);
        });

        it('starts from zero', function () {
            expect((new Usage())->plus(new Usage(7, 3))->totalTokens)->toBe(10);
        });

        it('serialises a sum, which has no provider payload, as its token counts', function () {
            $total = (new Usage(100, 20))->plus(new Usage(50, 5));

            expect($total->toArray())->toBe(['input_tokens' => 150, 'output_tokens' => 25, 'total_tokens' => 175]);
            expect(Usage::fromArray($total->toArray())->totalTokens)->toBe(175);
        });

        it('still serialises a provider usage as the provider sent it', function () {
            $raw = ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3, 'cached' => 9];

            expect(Usage::fromArray($raw)->toArray())->toBe($raw);
        });
    });
});
