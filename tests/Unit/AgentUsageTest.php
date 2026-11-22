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
use PapiAI\Core\Response;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;
use PapiAI\Core\Usage;

describe('Agent usage', function () {
    it('reports the tokens of every call in the run, and keeps those of the last call apart', function () {
        $provider = new ScriptedProvider(
            false,
            new Response('', toolCalls: [new ToolCall('c1', 'clock', [])], usage: new Usage(100, 10)),
            new Response('It is noon.', usage: new Usage(130, 6)),
        );
        $agent = new Agent($provider, 'm', tools: [Tool::make('clock', 'Tells the time', [], fn (): string => '12:00')]);

        $response = $agent->run('What time is it?');

        expect($response->usage->inputTokens)->toBe(230);
        expect($response->usage->outputTokens)->toBe(16);
        expect($response->getTotalTokens())->toBe(246);
        expect($response->lastCallUsage->inputTokens)->toBe(130);
    });

    it('reports a provider response as its own last call', function () {
        $response = new Response('hi', usage: new Usage(7, 3));

        expect($response->lastCallUsage)->toBe($response->usage);
    });
});
