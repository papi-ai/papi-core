<?php

declare(strict_types=1);

use PapiAI\Core\AgentRequest;

describe('AgentRequest', function () {
    it('stores prompt and options', function () {
        $request = new AgentRequest(
            prompt: 'Hello',
            options: ['maxTurns' => 5],
        );

        expect($request->prompt)->toBe('Hello');
        expect($request->options)->toBe(['maxTurns' => 5]);
        expect($request->messages)->toBe([]);
        expect($request->metadata)->toBe([]);
    });

    it('creates new instance with added option', function () {
        $request = new AgentRequest(prompt: 'Hello');
        $new = $request->withOption('temperature', 0.5);

        expect($new)->not->toBe($request);
        expect($new->options)->toBe(['temperature' => 0.5]);
        expect($request->options)->toBe([]);
    });

    it('creates new instance with messages', function () {
        $request = new AgentRequest(prompt: 'Hello');
        $messages = [['role' => 'user', 'content' => 'Hi']];
        $new = $request->withMessages($messages);

        expect($new->messages)->toBe($messages);
        expect($request->messages)->toBe([]);
    });

    it('creates new instance with metadata', function () {
        $request = new AgentRequest(prompt: 'Hello');
        $new = $request->withMetadata('request_id', 'abc-123');

        expect($new->metadata)->toBe(['request_id' => 'abc-123']);
        expect($request->metadata)->toBe([]);
    });
});
