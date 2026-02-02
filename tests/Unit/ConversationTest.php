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

use PapiAI\Core\Conversation;
use PapiAI\Core\Message;
use PapiAI\Core\ToolCall;

describe('Conversation', function () {
    describe('message management', function () {
        it('adds user messages', function () {
            $conversation = new Conversation();
            $conversation->addUser('Hello');

            expect($conversation->count())->toBe(1);
            expect($conversation->getLastMessage()->isUser())->toBeTrue();
        });

        it('adds assistant messages', function () {
            $conversation = new Conversation();
            $conversation->addAssistant('Hi there!');

            expect($conversation->getLastMessage()->isAssistant())->toBeTrue();
        });

        it('adds tool results', function () {
            $conversation = new Conversation();
            $conversation->addToolResult('call_123', ['result' => 42]);

            expect($conversation->getLastMessage()->isTool())->toBeTrue();
        });

        it('sets system prompt', function () {
            $conversation = new Conversation();
            $conversation->setSystem('You are helpful');

            $messages = $conversation->getMessages();
            expect($messages[0]->isSystem())->toBeTrue();
        });
    });

    describe('getMessages', function () {
        it('returns messages with system first', function () {
            $conversation = new Conversation();
            $conversation->setSystem('Be helpful');
            $conversation->addUser('Hello');
            $conversation->addAssistant('Hi!');

            $messages = $conversation->getMessages();

            expect($messages)->toHaveCount(3);
            expect($messages[0]->isSystem())->toBeTrue();
            expect($messages[1]->isUser())->toBeTrue();
            expect($messages[2]->isAssistant())->toBeTrue();
        });

        it('works without system prompt', function () {
            $conversation = new Conversation();
            $conversation->addUser('Hello');

            $messages = $conversation->getMessages();

            expect($messages)->toHaveCount(1);
            expect($messages[0]->isUser())->toBeTrue();
        });
    });

    describe('clear', function () {
        it('clears all messages', function () {
            $conversation = new Conversation();
            $conversation->setSystem('System');
            $conversation->addUser('Hello');
            $conversation->clear();

            expect($conversation->count())->toBe(0);
            expect($conversation->getMessages())->toBeEmpty();
        });

        it('optionally keeps system prompt', function () {
            $conversation = new Conversation();
            $conversation->setSystem('System');
            $conversation->addUser('Hello');
            $conversation->clear(keepSystem: true);

            expect($conversation->count())->toBe(0);

            $messages = $conversation->getMessages();
            expect($messages)->toHaveCount(1);
            expect($messages[0]->isSystem())->toBeTrue();
        });
    });

    describe('getLastAssistantMessage', function () {
        it('returns last assistant message', function () {
            $conversation = new Conversation();
            $conversation->addUser('Hello');
            $conversation->addAssistant('First response');
            $conversation->addUser('Follow up');
            $conversation->addAssistant('Second response');

            $last = $conversation->getLastAssistantMessage();

            expect($last->getText())->toBe('Second response');
        });

        it('returns null when no assistant messages', function () {
            $conversation = new Conversation();
            $conversation->addUser('Hello');

            expect($conversation->getLastAssistantMessage())->toBeNull();
        });
    });

    describe('fromMessages', function () {
        it('creates conversation from array', function () {
            $messages = [
                Message::system('Be helpful'),
                Message::user('Hello'),
                Message::assistant('Hi!'),
            ];

            $conversation = Conversation::fromMessages($messages);

            expect($conversation->count())->toBe(2); // system not counted
            expect($conversation->getMessages())->toHaveCount(3);
        });
    });
});
