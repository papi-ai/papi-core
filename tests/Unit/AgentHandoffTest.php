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
use PapiAI\Core\Conversation;
use PapiAI\Core\Handoff;
use PapiAI\Core\Response;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;
use PapiAI\Core\Usage;

describe('Agent handoff', function () {
    beforeEach(function () {
        $this->provider = new ScriptedProvider(false, new Response(
            'Let me pass you on.',
            toolCalls: [new ToolCall('c1', 'transfer_to_billing', []), new ToolCall('c2', 'clock', [])],
            usage: new Usage(40, 8),
        ));
        $this->clockRuns = 0;
        $this->agent = new Agent($this->provider, 'm', tools: [
            Tool::make('transfer_to_billing', 'Hands over to billing', [], fn (): Handoff => new Handoff('billing')),
            Tool::make('clock', 'Tells the time', [], function (): string {
                ++$this->clockRuns;

                return '12:00';
            }),
        ]);
    });

    it('ends the run when a tool hands the conversation over, without another model call', function () {
        $response = $this->agent->run('I was charged twice.');

        expect($response->handoff?->to)->toBe('billing');
        expect($this->provider->calls)->toHaveCount(1);
        expect($response->usage->totalTokens)->toBe(48);
    });

    it('answers every tool call of that turn, so the history stays valid', function () {
        $conversation = (new Conversation())->addUser('I was charged twice.');

        $this->agent->continue($conversation);

        $messages = $conversation->getMessages();
        expect(rolesOfMessages($messages))->toBe(['user', 'assistant', 'tool', 'tool']);
        expect($messages[2]->toolCallId)->toBe('c1');
        expect($messages[2]->getText())->toBe('Handed over to billing.');
        expect($messages[3]->getText())->toBe('Not run: the conversation was handed over to billing.');
        expect($this->clockRuns)->toBe(0);
    });

    it('reports no handoff on an ordinary run', function () {
        expect((new Agent(new ScriptedProvider(), 'm'))->run('Hi')->handoff)->toBeNull();
    });
});

function rolesOfMessages(array $messages): array
{
    return array_map(static fn ($message): string => $message->role->value, $messages);
}
