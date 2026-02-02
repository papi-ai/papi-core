<?php

declare(strict_types=1);

use PapiAI\Core\Response;
use PapiAI\Core\ToolCall;

describe('Response', function () {
    describe('construction', function () {
        it('creates a basic response', function () {
            $response = new Response(
                text: 'Hello, world!',
            );

            expect($response->text)->toBe('Hello, world!');
            expect($response->data)->toBeNull();
            expect($response->toolCalls)->toBeEmpty();
        });

        it('creates a response with data', function () {
            $response = new Response(
                text: '{"result": 42}',
                data: ['result' => 42],
            );

            expect($response->hasData())->toBeTrue();
            expect($response->data['result'])->toBe(42);
        });

        it('creates a response with tool calls', function () {
            $toolCalls = [
                new ToolCall('call_1', 'get_weather', ['city' => 'London']),
            ];

            $response = new Response(
                text: 'Let me check the weather.',
                toolCalls: $toolCalls,
            );

            expect($response->hasToolCalls())->toBeTrue();
            expect($response->toolCalls)->toHaveCount(1);
        });
    });

    describe('token usage', function () {
        it('returns token counts', function () {
            $response = new Response(
                text: 'Hello',
                usage: [
                    'input_tokens' => 10,
                    'output_tokens' => 5,
                ],
            );

            expect($response->getInputTokens())->toBe(10);
            expect($response->getOutputTokens())->toBe(5);
            expect($response->getTotalTokens())->toBe(15);
        });

        it('returns zero for missing usage', function () {
            $response = new Response(text: 'Hello');

            expect($response->getInputTokens())->toBe(0);
            expect($response->getOutputTokens())->toBe(0);
        });
    });

    describe('fromAnthropic', function () {
        it('parses text response', function () {
            $apiResponse = [
                'content' => [
                    ['type' => 'text', 'text' => 'Hello from Claude!'],
                ],
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 5,
                ],
                'stop_reason' => 'end_turn',
            ];

            $response = Response::fromAnthropic($apiResponse);

            expect($response->text)->toBe('Hello from Claude!');
            expect($response->getInputTokens())->toBe(10);
            expect($response->stopReason)->toBe('end_turn');
        });

        it('parses tool use response', function () {
            $apiResponse = [
                'content' => [
                    ['type' => 'text', 'text' => 'Let me check...'],
                    [
                        'type' => 'tool_use',
                        'id' => 'call_123',
                        'name' => 'get_weather',
                        'input' => ['city' => 'London'],
                    ],
                ],
                'stop_reason' => 'tool_use',
            ];

            $response = Response::fromAnthropic($apiResponse);

            expect($response->text)->toBe('Let me check...');
            expect($response->hasToolCalls())->toBeTrue();
            expect($response->toolCalls[0]->name)->toBe('get_weather');
            expect($response->toolCalls[0]->arguments)->toBe(['city' => 'London']);
        });
    });

    describe('fromOpenAI', function () {
        it('parses text response', function () {
            $apiResponse = [
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello from GPT!',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                ],
            ];

            $response = Response::fromOpenAI($apiResponse);

            expect($response->text)->toBe('Hello from GPT!');
            expect($response->stopReason)->toBe('stop');
        });

        it('parses tool call response', function () {
            $apiResponse = [
                'choices' => [
                    [
                        'message' => [
                            'content' => '',
                            'tool_calls' => [
                                [
                                    'id' => 'call_123',
                                    'function' => [
                                        'name' => 'get_weather',
                                        'arguments' => '{"city":"London"}',
                                    ],
                                ],
                            ],
                        ],
                        'finish_reason' => 'tool_calls',
                    ],
                ],
            ];

            $response = Response::fromOpenAI($apiResponse);

            expect($response->hasToolCalls())->toBeTrue();
            expect($response->toolCalls[0]->name)->toBe('get_weather');
        });
    });

    describe('withData', function () {
        it('creates new response with data', function () {
            $original = new Response(text: 'Result');
            $withData = $original->withData(['value' => 42]);

            expect($original->data)->toBeNull();
            expect($withData->data)->toBe(['value' => 42]);
            expect($withData->text)->toBe('Result');
        });
    });
});
