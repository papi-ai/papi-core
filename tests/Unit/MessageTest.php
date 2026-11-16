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

use PapiAI\Core\Message;
use PapiAI\Core\Role;
use PapiAI\Core\ToolCall;

describe('Message', function () {
    describe('factory methods', function () {
        it('creates user messages', function () {
            $message = Message::user('Hello');

            expect($message->role)->toBe(Role::User);
            expect($message->content)->toBe('Hello');
            expect($message->isUser())->toBeTrue();
        });

        it('creates system messages', function () {
            $message = Message::system('You are helpful');

            expect($message->role)->toBe(Role::System);
            expect($message->content)->toBe('You are helpful');
            expect($message->isSystem())->toBeTrue();
        });

        it('creates assistant messages', function () {
            $message = Message::assistant('Hi there!');

            expect($message->role)->toBe(Role::Assistant);
            expect($message->content)->toBe('Hi there!');
            expect($message->isAssistant())->toBeTrue();
        });

        it('creates assistant messages with tool calls', function () {
            $toolCalls = [
                new ToolCall('call_123', 'get_weather', ['city' => 'London']),
            ];

            $message = Message::assistant('Let me check...', $toolCalls);

            expect($message->hasToolCalls())->toBeTrue();
            expect($message->toolCalls)->toHaveCount(1);
            expect($message->toolCalls[0]->name)->toBe('get_weather');
        });

        it('creates tool result messages', function () {
            $message = Message::toolResult('call_123', ['temp' => 20]);

            expect($message->role)->toBe(Role::Tool);
            expect($message->toolCallId)->toBe('call_123');
            expect($message->isTool())->toBeTrue();
        });

        it('creates user messages with images', function () {
            $message = Message::userWithImage('What is this?', 'https://example.com/image.jpg');

            expect($message->role)->toBe(Role::User);
            expect($message->content)->toBeArray();
            expect($message->content[0]['type'])->toBe('text');
            expect($message->content[1]['type'])->toBe('image');
        });
    });

    describe('getText', function () {
        it('returns content for simple messages', function () {
            $message = Message::user('Hello');
            expect($message->getText())->toBe('Hello');
        });

        it('extracts text from multimodal messages', function () {
            $message = Message::userWithImage('What is this?', 'https://example.com/image.jpg');
            expect($message->getText())->toBe('What is this?');
        });
    });

    describe('toArray', function () {
        it('converts simple message to array', function () {
            $message = Message::user('Hello');
            $array = $message->toArray();

            expect($array['role'])->toBe('user');
            expect($array['content'])->toBe('Hello');
        });

        it('includes tool calls in array', function () {
            $toolCalls = [new ToolCall('call_123', 'test', [])];
            $message = Message::assistant('', $toolCalls);
            $array = $message->toArray();

            expect($array['tool_calls'])->toHaveCount(1);
            expect($array['tool_calls'][0]['name'])->toBe('test');
        });

        it('includes tool call id for tool messages', function () {
            $message = Message::toolResult('call_123', 'result');
            $array = $message->toArray();

            expect($array['tool_call_id'])->toBe('call_123');
        });
    });

    describe('replay', function () {
        it('carries nothing to replay unless a provider left something', function () {
            expect(Message::assistant('Hi')->replayFor('anthropic'))->toBe([]);
        });

        it('gives each provider back only what that provider left', function () {
            $message = Message::assistant('Hi', replay: [
                'anthropic' => [['type' => 'thinking', 'thinking' => '', 'signature' => 'sig']],
            ]);

            expect($message->replayFor('anthropic'))->toBe([['type' => 'thinking', 'thinking' => '', 'signature' => 'sig']]);
            expect($message->replayFor('openai'))->toBe([]);
        });

        it('survives a round trip through JSON as plain data', function () {
            $replay = ['anthropic' => [['type' => 'redacted_thinking', 'data' => 'opaque']]];
            $message = Message::assistant('Hi', [new ToolCall('call_1', 'lookup', [])], $replay);

            $restored = Message::fromArray(json_decode(json_encode($message->toArray()), true));

            expect($restored->replayFor('anthropic'))->toBe([['type' => 'redacted_thinking', 'data' => 'opaque']]);
            expect($restored->toolCalls[0]->id)->toBe('call_1');
        });

        it('leaves replay out of the array form when there is none', function () {
            expect(Message::assistant('Hi')->toArray())->not->toHaveKey('replay');
        });
    });
});
