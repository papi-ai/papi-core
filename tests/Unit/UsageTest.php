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
});
