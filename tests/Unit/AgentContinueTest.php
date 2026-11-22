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
use PapiAI\Core\AgentRequest;
use PapiAI\Core\Contracts\ContinuesConversationInterface;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Conversation;
use PapiAI\Core\Message;
use PapiAI\Core\Response;
use PapiAI\Core\Role;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;

function rolesOf(array $messages): array
{
    return array_map(static fn (Message $message): string => $message->role->value, $messages);
}

describe('Agent::continue', function () {
    it('answers the conversation so far, under its own instructions', function () {
        $provider = new ScriptedProvider(false, new Response('It was 1042.'));
        $agent = new Agent($provider, 'm', instructions: 'You work in billing.');
        $conversation = (new Conversation())->setSystem('You are triage.')
            ->addUser('I was charged twice.')->addAssistant('Which invoice?')->addUser('The last one.');

        $response = $agent->continue($conversation);

        expect($agent)->toBeInstanceOf(ContinuesConversationInterface::class);
        expect($response->text)->toBe('It was 1042.');
        expect(rolesOf($provider->calls[0]['messages']))->toBe(['system', 'user', 'assistant', 'user']);
        expect($provider->calls[0]['messages'][0]->getText())->toBe('You work in billing.');
    });

    it('keeps the conversation\'s instructions when the agent has none', function () {
        $provider = new ScriptedProvider();
        $conversation = (new Conversation())->setSystem('You are triage.')->addUser('Hello');

        (new Agent($provider, 'm'))->continue($conversation);

        expect($provider->calls[0]['messages'][0]->getText())->toBe('You are triage.');
    });

    it('appends everything the run added, so saving the conversation is enough', function () {
        $provider = new ScriptedProvider(
            false,
            new Response('', toolCalls: [new ToolCall('c1', 'clock', [])]),
            new Response('It is noon.'),
        );
        $agent = new Agent($provider, 'm', tools: [Tool::make('clock', 'Tells the time', [], fn (): string => '12:00')]);
        $conversation = (new Conversation())->addUser('What time is it?');

        $agent->continue($conversation);

        expect(rolesOf($conversation->getMessages()))->toBe(['user', 'assistant', 'tool', 'assistant']);
        expect($conversation->getLastMessage()?->getText())->toBe('It is noon.');
        expect(Conversation::fromArray($conversation->toArray())->count())->toBe(4);
    });

    it('leaves the conversation as it was when the run fails part-way', function () {
        $provider = new ScriptedProvider(
            false,
            new Response('', toolCalls: [new ToolCall('c1', 'clock', [])]),
            new RuntimeException('Provider is down'),
        );
        $agent = new Agent($provider, 'm', tools: [Tool::make('clock', 'Tells the time', [], fn (): string => '12:00')]);
        $conversation = (new Conversation())->addUser('What time is it?');

        expect(fn () => $agent->continue($conversation))->toThrow(RuntimeException::class, 'Provider is down');
        expect(rolesOf($conversation->getMessages()))->toBe(['user']);
    });

    it('hands middleware the conversation, with the last user message as the prompt', function () {
        $seen = new class () implements MiddlewareInterface {
            public ?AgentRequest $request = null;

            public function process(AgentRequest $request, callable $next): Response
            {
                $this->request = $request;

                return $next($request);
            }
        };
        $agent = new Agent(new ScriptedProvider(), 'm', middleware: [$seen]);

        $agent->continue((new Conversation())->addUser('First')->addAssistant('Yes?')->addUser('Second'));

        expect($seen->request?->prompt)->toBe('Second');
        expect(rolesOf($seen->request->messages))->toBe(['user', 'assistant', 'user']);
        expect($seen->request->messages[0]->role)->toBe(Role::User);
    });
});
