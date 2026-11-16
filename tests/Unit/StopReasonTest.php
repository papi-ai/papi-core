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

use PapiAI\Core\Agent;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Response;
use PapiAI\Core\StopReason;
use PapiAI\Core\ToolCall;

describe('Why a response stopped', function () {
    it('is Other when the provider does not say', function () {
        expect((new Response('hi'))->stoppedBecause)->toBe(StopReason::Other);
    });

    it('reports truncation only when the output ceiling cut the answer short', function () {
        expect((new Response('hi', stoppedBecause: StopReason::MaxTokens))->isTruncated())->toBeTrue();
        expect((new Response('hi', stoppedBecause: StopReason::Stop))->isTruncated())->toBeFalse();
    });

    it('survives copying, with data or with new messages', function () {
        $response = new Response('hi', stopReason: 'length', stoppedBecause: StopReason::MaxTokens);

        foreach ([$response->withData(['a' => 1]), $response->withMessages([])] as $copy) {
            expect($copy->stoppedBecause)->toBe(StopReason::MaxTokens);
            expect($copy->stopReason)->toBe('length');
        }
    });

    it('replaces only the messages when copied with new ones', function () {
        $copy = (new Response('hi', data: ['a' => 1]))->withMessages(['history']);

        expect($copy->messages)->toBe(['history']);
        expect($copy->data)->toBe(['a' => 1]);
        expect($copy->text)->toBe('hi');
    });

    it('normalises the Chat Completions finish reasons', function () {
        $reasons = [
            'stop' => StopReason::Stop,
            'length' => StopReason::MaxTokens,
            'model_length' => StopReason::MaxTokens,
            'tool_calls' => StopReason::ToolCalls,
            'function_call' => StopReason::ToolCalls,
            'content_filter' => StopReason::Refused,
            'insufficient_system_resource' => StopReason::Other,
        ];

        foreach ($reasons as $raw => $expected) {
            $response = Response::fromOpenAI(['choices' => [['message' => ['content' => 'x'], 'finish_reason' => $raw]]]);

            expect($response->stoppedBecause)->toBe($expected, $raw);
            expect($response->stopReason)->toBe($raw);
        }
    });

    it('calls a turn with tool calls tool_calls, whatever finish reason came with it', function () {
        $response = Response::fromOpenAI(['choices' => [[
            'message' => ['content' => null, 'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'f', 'arguments' => '{}']]]],
            'finish_reason' => 'stop',
        ]]]);

        expect($response->stoppedBecause)->toBe(StopReason::ToolCalls);
    });

    it('normalises the Anthropic stop reasons', function () {
        $reasons = [
            'end_turn' => StopReason::Stop,
            'stop_sequence' => StopReason::Stop,
            'max_tokens' => StopReason::MaxTokens,
            'model_context_window_exceeded' => StopReason::MaxTokens,
            'tool_use' => StopReason::ToolCalls,
            'refusal' => StopReason::Refused,
            'pause_turn' => StopReason::Other,
        ];

        foreach ($reasons as $raw => $expected) {
            expect(Response::fromAnthropic(['content' => [], 'stop_reason' => $raw])->stoppedBecause)->toBe($expected, $raw);
        }
    });

    it('keeps the provider\'s stop reason through an agent run, structured or not', function () {
        $provider = Mockery::mock(ProviderInterface::class);
        $provider->shouldReceive('chat')->andReturn(new Response('{"a": 1}', stopReason: 'length', stoppedBecause: StopReason::MaxTokens));
        $provider->shouldReceive('supportsStructuredOutput')->andReturn(true);
        $agent = new Agent(provider: $provider, model: 'test-model');

        expect($agent->run('hi')->stoppedBecause)->toBe(StopReason::MaxTokens);
        expect($agent->run('hi', ['outputSchema' => PapiAI\Core\Schema\Schema::object(['a' => PapiAI\Core\Schema\Schema::integer()])])->stoppedBecause)
            ->toBe(StopReason::MaxTokens);

        Mockery::close();
    });

    it('never reports a turn with tool calls as truncated', function () {
        $response = new Response('', toolCalls: [new ToolCall('c1', 'f', [])], stoppedBecause: StopReason::ToolCalls);

        expect($response->isTruncated())->toBeFalse();
    });
});
