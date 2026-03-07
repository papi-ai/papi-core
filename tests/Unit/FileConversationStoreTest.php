<?php

declare(strict_types=1);

use PapiAI\Core\Contracts\ConversationStoreInterface;
use PapiAI\Core\Conversation;
use PapiAI\Core\Storage\FileConversationStore;

describe('FileConversationStore', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/papi-test-store-' . uniqid();
        $this->store = new FileConversationStore($this->dir);
    });

    afterEach(function () {
        // Clean up
        $files = glob($this->dir . '/*.json') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    });

    it('implements ConversationStoreInterface', function () {
        expect($this->store)->toBeInstanceOf(ConversationStoreInterface::class);
    });

    it('creates directory if it does not exist', function () {
        expect(is_dir($this->dir))->toBeTrue();
    });

    describe('save and load', function () {
        it('persists and retrieves a conversation', function () {
            $conversation = new Conversation();
            $conversation->setSystem('Be helpful');
            $conversation->addUser('Hello');
            $conversation->addAssistant('Hi there!');

            $this->store->save('conv-1', $conversation);
            $loaded = $this->store->load('conv-1');

            expect($loaded)->not->toBeNull();
            $messages = $loaded->getMessages();
            expect($messages)->toHaveCount(3);
            expect($messages[0]->isSystem())->toBeTrue();
            expect($messages[0]->getText())->toBe('Be helpful');
            expect($messages[1]->getText())->toBe('Hello');
            expect($messages[2]->getText())->toBe('Hi there!');
        });

        it('returns null for non-existent conversation', function () {
            expect($this->store->load('non-existent'))->toBeNull();
        });

        it('overwrites existing conversation', function () {
            $conv1 = new Conversation();
            $conv1->addUser('First');
            $this->store->save('conv-1', $conv1);

            $conv2 = new Conversation();
            $conv2->addUser('Second');
            $this->store->save('conv-1', $conv2);

            $loaded = $this->store->load('conv-1');
            expect($loaded->getMessages()[0]->getText())->toBe('Second');
        });

        it('preserves tool calls in messages', function () {
            $conversation = new Conversation();
            $conversation->addUser('What is the weather?');
            $conversation->addAssistant('Let me check', [
                new \PapiAI\Core\ToolCall('tc_1', 'get_weather', ['city' => 'London']),
            ]);
            $conversation->addToolResult('tc_1', ['temp' => 20]);

            $this->store->save('conv-tools', $conversation);
            $loaded = $this->store->load('conv-tools');

            $messages = $loaded->getMessages();
            expect($messages[1]->hasToolCalls())->toBeTrue();
            expect($messages[1]->toolCalls[0]->name)->toBe('get_weather');
            expect($messages[2]->isTool())->toBeTrue();
            expect($messages[2]->toolCallId)->toBe('tc_1');
        });
    });

    describe('delete', function () {
        it('removes a conversation', function () {
            $conv = new Conversation();
            $conv->addUser('Hello');
            $this->store->save('conv-1', $conv);

            $this->store->delete('conv-1');

            expect($this->store->load('conv-1'))->toBeNull();
        });

        it('does nothing for non-existent conversation', function () {
            $this->store->delete('non-existent');

            expect(true)->toBeTrue(); // No exception
        });
    });

    describe('list', function () {
        it('returns conversation IDs', function () {
            $conv = new Conversation();
            $conv->addUser('Hello');

            $this->store->save('conv-a', $conv);
            $this->store->save('conv-b', $conv);
            $this->store->save('conv-c', $conv);

            $ids = $this->store->list();

            expect($ids)->toHaveCount(3);
            expect($ids)->toContain('conv-a');
            expect($ids)->toContain('conv-b');
            expect($ids)->toContain('conv-c');
        });

        it('respects limit', function () {
            $conv = new Conversation();
            $conv->addUser('Hello');

            $this->store->save('conv-1', $conv);
            $this->store->save('conv-2', $conv);
            $this->store->save('conv-3', $conv);

            $ids = $this->store->list(2);

            expect($ids)->toHaveCount(2);
        });

        it('returns empty array when no conversations', function () {
            expect($this->store->list())->toBe([]);
        });
    });

    describe('sanitization', function () {
        it('sanitizes special characters in IDs', function () {
            $conv = new Conversation();
            $conv->addUser('Hello');

            $this->store->save('user/conv:1', $conv);
            $loaded = $this->store->load('user/conv:1');

            expect($loaded)->not->toBeNull();
        });
    });
});
