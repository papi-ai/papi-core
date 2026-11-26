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
use PapiAI\Core\Handoffs;
use PapiAI\Core\Response;
use PapiAI\Core\Storage\FileConversationStore;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;
use PapiAI\Core\Usage;

function transferTo(string $agent, string $callId = 'c1'): Response
{
    return new Response('', toolCalls: [new ToolCall($callId, 'transfer_to_' . $agent, [])], usage: new Usage(10, 2));
}

describe('Handoffs', function () {
    beforeEach(function () {
        $this->triage = new ScriptedProvider(false, transferTo('billing'));
        $this->billing = new ScriptedProvider(
            false,
            new Response('Which invoice was it?', usage: new Usage(20, 5)),
            new Response('Refunded invoice 1042.', usage: new Usage(30, 5)),
        );
        $this->desk = new Handoffs([
            'triage' => new Agent($this->triage, 'm', instructions: 'You route customers.'),
            'billing' => new Agent($this->billing, 'm', instructions: 'You handle billing.'),
            'technical' => new Agent(new ScriptedProvider(), 'm', instructions: 'You fix things.'),
        ], start: 'triage', descriptions: ['billing' => 'Charges, invoices and refunds']);
    });

    it('6. lets an agent hand the conversation over, and keeps the next message with the new agent', function () {
        $store = new FileConversationStore(sys_get_temp_dir() . '/papi-handoffs-' . uniqid());
        $conversation = (new Conversation())->addUser('I was charged twice this month.');

        $first = $this->desk->reply($conversation);
        $store->save('chat-1', $conversation);

        expect($first->text)->toBe('Which invoice was it?');
        expect($first->usage->totalTokens)->toBe(37);
        expect($this->billing->calls[0]['messages'][0]->getText())->toBe('You handle billing.');
        expect($this->billing->calls[0]['messages'][1]->getText())->toBe('I was charged twice this month.');

        // The next request: the conversation comes back from storage, and goes straight to billing.
        $conversation = $store->load('chat-1');
        $conversation->addUser('It was invoice 1042.');

        $second = $this->desk->reply($conversation);

        expect($second->text)->toBe('Refunded invoice 1042.');
        expect($this->desk->current($conversation))->toBe('billing');
        expect($this->triage->calls)->toHaveCount(1);
        expect($this->billing->calls)->toHaveCount(2);
    });

    it('starts with the agent it was told to', function () {
        expect($this->desk->current(new Conversation()))->toBe('triage');
    });

    it('offers each agent a transfer to every other, described as asked', function () {
        $this->desk->reply((new Conversation())->addUser('Hello'));

        $tools = array_column($this->triage->calls[0]['options']['tools'], 'description', 'name');

        expect(array_keys($tools))->toBe(['transfer_to_billing', 'transfer_to_technical']);
        expect($tools['transfer_to_billing'])->toContain('Charges, invoices and refunds');
    });

    it('keeps every transfer call answered in the stored conversation', function () {
        $conversation = (new Conversation())->addUser('I was charged twice this month.');

        $this->desk->reply($conversation);

        $roles = array_map(static fn ($message): string => $message->role->value, $conversation->getMessages());
        expect($roles)->toBe(['user', 'assistant', 'tool', 'assistant']);
        expect($conversation->getMessages()[2]->getText())->toBe('Handed over to billing.');
    });

    it('does not show an agent the tool calls another agent made for itself', function () {
        $lookup = new ScriptedProvider(
            false,
            new Response('', toolCalls: [new ToolCall('k1', 'find_order', [])]),
            new Response('', toolCalls: [new ToolCall('k2', 'transfer_to_closer', [])]),
        );
        $closer = new ScriptedProvider(false, new Response('All done.'));
        $desk = new Handoffs([
            'lookup' => new Agent($lookup, 'm', tools: [Tool::make('find_order', 'Finds an order', [], fn (): string => 'Order 7')]),
            'closer' => new Agent($closer, 'm'),
        ], start: 'lookup');
        $conversation = (new Conversation())->addUser('Close my order.');

        $desk->reply($conversation);

        $shown = array_map(static fn ($message): string => $message->role->value, $closer->calls[0]['messages']);
        expect($shown)->toBe(['user', 'user']);
        expect($conversation->count())->toBe(6);
    });

    it('shows the receiving agent a handoff as something that happened to it, not a call it made', function () {
        $conversation = (new Conversation())->addUser('I was charged twice this month.');

        $this->desk->reply($conversation);

        $sent = array_map(
            static fn ($message): array => [$message->role->value, $message->getText(), $message->toolCalls],
            $this->billing->calls[0]['messages'],
        );
        expect($sent)->toBe([
            ['system', 'You handle billing.', null],
            ['user', 'I was charged twice this month.', null],
            ['user', '[triage handed this conversation over to you.]', null],
        ]);
    });

    it('words a handoff for whoever reads it', function () {
        $triage = new ScriptedProvider(false, transferTo('billing', 't1'), new Response('Anything else?'));
        $billing = new ScriptedProvider(false, new Response('Let me pass you back.', toolCalls: [new ToolCall('b1', 'transfer_to_triage', [])]));
        $desk = new Handoffs(['triage' => new Agent($triage, 'm'), 'billing' => new Agent($billing, 'm')], start: 'triage');

        $desk->reply((new Conversation())->addUser('Hello'));

        $read = array_map(static fn ($message): string => $message->role->value . ': ' . $message->getText(), $triage->calls[1]['messages']);
        expect($read)->toBe([
            'user: Hello',
            'user: [You handed this conversation over to billing.]',
            'assistant: Let me pass you back.',
            'user: [billing handed this conversation over to you.]',
        ]);
    });

    it('names both agents of a handoff to a third', function () {
        $triage = new ScriptedProvider(false, transferTo('billing', 't1'));
        $billing = new ScriptedProvider(false, transferTo('technical', 'b1'));
        $technical = new ScriptedProvider(false, new Response('Fixed.'));
        $desk = new Handoffs([
            'triage' => new Agent($triage, 'm'),
            'billing' => new Agent($billing, 'm'),
            'technical' => new Agent($technical, 'm'),
        ], start: 'triage');

        $desk->reply((new Conversation())->addUser('Hello'));

        expect($technical->calls[0]['messages'][1]->getText())->toBe('[triage handed this conversation over to billing.]');
        expect($technical->calls[0]['messages'][2]->getText())->toBe('[billing handed this conversation over to you.]');
    });

    it('leaves the conversation as it was when an agent fails after a handoff', function () {
        $desk = new Handoffs([
            'triage' => new Agent(new ScriptedProvider(false, transferTo('billing')), 'm'),
            'billing' => new Agent(new ScriptedProvider(false, new RuntimeException('Provider is down')), 'm'),
        ], start: 'triage');
        $conversation = (new Conversation())->addUser('Hello');

        expect(fn () => $desk->reply($conversation))->toThrow(RuntimeException::class, 'Provider is down');
        expect($conversation->count())->toBe(1);
    });

    it('stops passing the conversation around after five hops', function () {
        $ping = new ScriptedProvider(false, ...array_map(fn (int $n) => transferTo('pong', "a{$n}"), range(1, 9)));
        $pong = new ScriptedProvider(false, ...array_map(fn (int $n) => transferTo('ping', "b{$n}"), range(1, 9)));
        $desk = new Handoffs(['ping' => new Agent($ping, 'm'), 'pong' => new Agent($pong, 'm')], start: 'ping');

        $response = $desk->reply((new Conversation())->addUser('Hello'));

        expect(count($ping->calls) + count($pong->calls))->toBe(6);
        expect($response->handoff)->not->toBeNull();
    });

    it('refuses agents it cannot work with', function () {
        expect(fn () => new Handoffs(['a' => new Agent(new ScriptedProvider(), 'm')], start: 'b'))
            ->toThrow(InvalidArgumentException::class, 'Handoffs cannot start with "b": it has no agent of that name. It has: "a".');
        expect(fn () => new Handoffs(['Billing Team' => new Agent(new ScriptedProvider(), 'm')], start: 'Billing Team'))
            ->toThrow(InvalidArgumentException::class, 'An agent\'s name becomes part of a tool name, so "Billing Team" must be letters, digits and "_" only.');
    });
});
