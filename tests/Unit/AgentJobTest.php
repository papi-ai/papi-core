<?php

declare(strict_types=1);

use PapiAI\Core\AgentJob;

describe('AgentJob', function () {
    it('stores agent class, prompt, options, and callback URL', function () {
        $job = new AgentJob(
            agentClass: 'App\\Agents\\ChatAgent',
            prompt: 'Hello',
            options: ['model' => 'gpt-4o'],
            callbackUrl: 'https://example.com/callback',
        );

        expect($job->agentClass)->toBe('App\\Agents\\ChatAgent');
        expect($job->prompt)->toBe('Hello');
        expect($job->options)->toBe(['model' => 'gpt-4o']);
        expect($job->callbackUrl)->toBe('https://example.com/callback');
    });

    it('defaults options and callback to empty/null', function () {
        $job = new AgentJob(agentClass: 'MyAgent', prompt: 'Hi');

        expect($job->options)->toBe([]);
        expect($job->callbackUrl)->toBeNull();
    });

    it('serializes to array', function () {
        $job = new AgentJob(
            agentClass: 'MyAgent',
            prompt: 'Hello',
            options: ['key' => 'val'],
            callbackUrl: 'https://example.com',
        );

        expect($job->toArray())->toBe([
            'agentClass' => 'MyAgent',
            'prompt' => 'Hello',
            'options' => ['key' => 'val'],
            'callbackUrl' => 'https://example.com',
        ]);
    });

    it('deserializes from array', function () {
        $data = [
            'agentClass' => 'MyAgent',
            'prompt' => 'Hello',
            'options' => ['key' => 'val'],
            'callbackUrl' => 'https://example.com',
        ];

        $job = AgentJob::fromArray($data);

        expect($job->agentClass)->toBe('MyAgent');
        expect($job->prompt)->toBe('Hello');
        expect($job->options)->toBe(['key' => 'val']);
        expect($job->callbackUrl)->toBe('https://example.com');
    });
});
