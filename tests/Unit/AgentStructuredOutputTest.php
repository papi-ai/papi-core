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
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Usage;

function verdictSchema(): Schema
{
    return Schema::object(['approved' => Schema::boolean(), 'issues' => Schema::array(Schema::string())]);
}

describe('Agent structured output', function () {
    it('states the schema in the instructions for a provider that cannot enforce one', function () {
        $provider = new ScriptedProvider(false, new Response('{"approved": true, "issues": []}'));
        $agent = new Agent($provider, 'm', instructions: 'You review drafts.');

        $response = $agent->run('Review this.', ['outputSchema' => verdictSchema()]);

        $sent = $provider->calls[0]['messages'][0]->getText();
        expect($sent)->toStartWith('You review drafts.');
        expect($sent)->toContain('single JSON object');
        expect($sent)->toContain('"approved":{"type":"boolean"}');
        expect($response->data)->toBe(['approved' => true, 'issues' => []]);
        expect($response->messages[0]->getText())->toBe('You review drafts.');
    });

    it('states the schema even when the agent has no instructions', function () {
        $provider = new ScriptedProvider(false, new Response('{"approved": true, "issues": []}'));

        (new Agent($provider, 'm'))->run('Review this.', ['outputSchema' => verdictSchema()]);

        expect($provider->calls[0]['messages'][0]->isSystem())->toBeTrue();
        expect($provider->calls[0]['messages'][0]->getText())->toContain('single JSON object');
    });

    it('hands the schema to a provider that cannot enforce one, so it can still ask for JSON', function () {
        $provider = new ScriptedProvider(false, new Response('{"approved": true, "issues": []}'));

        (new Agent($provider, 'm'))->run('Review this.', ['outputSchema' => verdictSchema()]);

        expect($provider->calls[0]['options']['outputSchema'])->toBe(verdictSchema()->toJsonSchema());
    });

    it('leaves the instructions alone for a provider that enforces the schema', function () {
        $provider = new ScriptedProvider(true, new Response('{"approved": true, "issues": []}'));

        (new Agent($provider, 'm', instructions: 'You review drafts.'))->run('Review this.', ['outputSchema' => verdictSchema()]);

        expect($provider->calls[0]['messages'][0]->getText())->toBe('You review drafts.');
        expect($provider->calls[0]['options'])->toHaveKey('outputSchema');
    });

    it('asks once for a corrected object when the reply does not fit the schema', function (string $bad) {
        $provider = new ScriptedProvider(
            true,
            new Response($bad, usage: new Usage(10, 5)),
            new Response('{"approved": false, "issues": ["Too long"]}', usage: new Usage(20, 5)),
        );

        $response = (new Agent($provider, 'm', maxTurns: 1))->run('Review this.', ['outputSchema' => verdictSchema()]);

        $repair = end($provider->calls[1]['messages']);
        expect($response->data)->toBe(['approved' => false, 'issues' => ['Too long']]);
        expect($repair->isUser())->toBeTrue();
        expect($repair->getText())->toContain('corrected JSON object');
        expect($response->usage->totalTokens)->toBe(40);
    })->with([
        'not JSON' => ['Sure! Here is my review.'],
        'the wrong shape' => ['{"approved": "yes"}'],
    ]);

    it('gives up after one correction', function () {
        $provider = new ScriptedProvider(true, new Response('nope'), new Response('still nope'), new Response('{"approved": true, "issues": []}'));

        expect(fn () => (new Agent($provider, 'm'))->run('Review this.', ['outputSchema' => verdictSchema()]))
            ->toThrow(InvalidArgumentException::class, 'Failed to parse structured output');
        expect($provider->calls)->toHaveCount(2);
    });
});
