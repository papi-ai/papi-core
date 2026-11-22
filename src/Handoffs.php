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

/**
 * A group of agents that can pass a conversation to one another.
 *
 * Each agent is given a `transfer_to_<name>` tool for every other agent. Which agent is current
 * is read from the conversation itself: it is the target of the latest transfer call, or the
 * starting agent when there is none. So storing the conversation is all it takes for the next
 * message to reach the right agent.
 *
 * An agent is shown the user's messages, the assistants' text and the transfers. Tool calls an
 * agent made for itself are left out for the others, as is provider replay data: both are bound
 * to the agent that produced them.
 */
final class Handoffs
{
    private const TOOL_PREFIX = 'transfer_to_';

    /**
     * @param array<string, AgentInterface&ContinuesConversationInterface> $agents       By name: letters, digits and "_"
     * @param string                                                         $start        The agent a new conversation begins with
     * @param array<string, string>                                          $descriptions What each agent handles, by name, for the transfer tools
     * @param int                                                            $maxHops      How many handoffs one reply may make
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
     * What the agents added is appended to the conversation; when an agent throws, the
     * conversation is left as it was.
     *
     * @return Response The answering agent's response, with the usage of every agent that took part
     */
    public function reply(Conversation $conversation): Response
    {
        $whole = Conversation::fromMessages($conversation->getMessages());
        $current = $this->current($whole);
        $added = [];
        $usage = new Usage();
        $hops = 0;

        do {
            $shown = $this->shownToAgents($whole);
            $before = count($shown->getMessages());
            $response = $this->agents[$current]->continue($shown);
            $usage = $usage->plus($response->usage);

            foreach (array_slice($shown->getMessages(), $before) as $message) {
                $whole->addMessage($message);
                $added[] = $message;
            }

            $current = $response->handoff?->to;
        } while ($current !== null && isset($this->agents[$current]) && $hops++ < $this->maxHops);

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
     * The conversation every agent can read: user messages, assistant text, and the transfers
     * with their results.
     */
    private function shownToAgents(Conversation $whole): Conversation
    {
        $shown = new Conversation();
        $transferIds = [];

        foreach ($whole->getMessages() as $message) {
            if ($message->isAssistant()) {
                $transfers = array_values(array_filter($message->toolCalls ?? [], $this->isTransfer(...)));
                array_push($transferIds, ...array_map(static fn (ToolCall $toolCall): string => $toolCall->id, $transfers));

                if ($transfers !== [] || $message->getText() !== '') {
                    $shown->addMessage(Message::assistant($message->getText(), $transfers ?: null));
                }

                continue;
            }

            if (!$message->isTool() || in_array($message->toolCallId, $transferIds, true)) {
                $shown->addMessage($message);
            }
        }

        return $shown;
    }
}
