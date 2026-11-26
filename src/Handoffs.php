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

namespace PapiAI\Core;

use InvalidArgumentException;
use PapiAI\Core\Contracts\AgentInterface;
use PapiAI\Core\Contracts\ContinuesConversationInterface;
use PapiAI\Core\Exception\HandoffLimitException;

/**
 * A group of agents that can pass a conversation to one another.
 *
 * Each agent is given a `transfer_to_<name>` tool for every other agent. Which agent is current
 * is read from the conversation itself: it is the target of the latest transfer call, or the
 * starting agent when there is none. So storing the conversation is all it takes for the next
 * message to reach the right agent.
 *
 * An agent is shown the user's messages, what the assistants said, and each handoff as a note
 * in the transcript ("[triage handed this conversation over to you.]"), so a transfer reads as
 * something that happened and not as a call the reader made. Tool calls and their results are
 * left out, as is provider replay data: both are bound to the agent that produced them. The
 * stored conversation keeps every call and result.
 */
final class Handoffs
{
    private const TOOL_PREFIX = 'transfer_to_';

    /**
     * @param array<string, AgentInterface&ContinuesConversationInterface> $agents       By name: letters, digits and "_"
     * @param string                                                         $start        The agent a new conversation begins with
     * @param array<string, string>                                          $descriptions What each agent handles, by name, for the transfer tools
     * @param int                                                            $maxHops      How many handoffs one reply may follow
     *
     * @throws InvalidArgumentException When a name cannot be a tool name, or the starting agent is not one of them
     */
    public function __construct(
        private readonly array $agents,
        private readonly string $start,
        array $descriptions = [],
        private readonly int $maxHops = 5,
    ) {
        foreach (array_keys($agents) as $name) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $name) !== 1) {
                throw new InvalidArgumentException(sprintf('An agent\'s name becomes part of a tool name, so "%s" must be letters, digits and "_" only.', $name));
            }
        }

        if (!isset($agents[$start])) {
            throw new InvalidArgumentException(sprintf(
                'Handoffs cannot start with "%s": it has no agent of that name. It has: %s.',
                $start,
                implode(', ', array_map(static fn (string $name): string => '"' . $name . '"', array_keys($agents))),
            ));
        }

        $this->offerTransfers($descriptions);
    }

    /**
     * Answer the conversation with its current agent, following handoffs until an agent answers.
     * What the agents added is appended to the conversation; when an agent throws, or the
     * handoff limit is passed, the conversation is left as it was.
     *
     * @return Response The answering agent's response, with the usage of every agent that took part
     *
     * @throws HandoffLimitException When the agents hand over more often than the limit, with no answer
     */
    public function reply(Conversation $conversation): Response
    {
        $whole = Conversation::fromMessages($conversation->getMessages());
        $current = $this->current($whole);
        $added = [];
        $usage = new Usage();
        $hops = 0;

        do {
            $shown = $this->shownTo($current, $whole);
            $before = count($shown->getMessages());
            $response = $this->agents[$current]->continue($shown);
            $usage = $usage->plus($response->usage);

            foreach (array_slice($shown->getMessages(), $before) as $message) {
                $whole->addMessage($message);
                $added[] = $message;
            }

            $current = $response->handoff?->to;
            $handedOver = $current !== null && isset($this->agents[$current]);

            if ($handedOver && ++$hops > $this->maxHops) {
                throw new HandoffLimitException($this->maxHops, $current, $usage);
            }
        } while ($handedOver);

        foreach ($added as $message) {
            $conversation->addMessage($message);
        }

        return $response->withRunUsage($usage);
    }

    /**
     * The name of the agent the next message goes to.
     */
    public function current(Conversation $conversation): string
    {
        foreach (array_reverse($conversation->getMessages()) as $message) {
            foreach (array_reverse($message->toolCalls ?? []) as $toolCall) {
                if ($this->isTransfer($toolCall)) {
                    return substr($toolCall->name, strlen(self::TOOL_PREFIX));
                }
            }
        }

        return $this->start;
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function offerTransfers(array $descriptions): void
    {
        foreach ($this->agents as $name => $agent) {
            foreach (array_keys($this->agents) as $other) {
                if ($other === $name) {
                    continue;
                }

                $handles = isset($descriptions[$other]) ? ' It handles: ' . $descriptions[$other] . '.' : '';
                $agent->addTool(Tool::make(
                    self::TOOL_PREFIX . $other,
                    sprintf('Hand the conversation over to %s, who continues it from here.%s', $other, $handles),
                    [],
                    static fn (): Handoff => new Handoff($other),
                ));
            }
        }
    }

    private function isTransfer(ToolCall $toolCall): bool
    {
        return str_starts_with($toolCall->name, self::TOOL_PREFIX)
            && isset($this->agents[substr($toolCall->name, strlen(self::TOOL_PREFIX))]);
    }

    /**
     * The conversation as one agent reads it: user messages, what the assistants said, and a
     * note for each handoff, worded for the reader.
     */
    private function shownTo(string $reader, Conversation $whole): Conversation
    {
        $shown = new Conversation();
        $holder = $this->start;

        foreach ($whole->getMessages() as $message) {
            if ($message->isTool()) {
                continue;
            }

            if (!$message->isAssistant()) {
                $shown->addMessage($message);

                continue;
            }

            if ($message->getText() !== '') {
                $shown->addAssistant($message->getText());
            }

            foreach (array_filter($message->toolCalls ?? [], $this->isTransfer(...)) as $transfer) {
                $target = substr($transfer->name, strlen(self::TOOL_PREFIX));
                $shown->addUser($this->handoffAsReadBy($reader, $holder, $target));
                $holder = $target;
            }
        }

        return $shown;
    }

    private function handoffAsReadBy(string $reader, string $from, string $to): string
    {
        return sprintf(
            '[%s handed this conversation over to %s.]',
            $from === $reader ? 'You' : $from,
            $to === $reader ? 'you' : $to,
        );
    }
}
