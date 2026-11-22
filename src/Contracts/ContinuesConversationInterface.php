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

namespace PapiAI\Core\Contracts;

use PapiAI\Core\Conversation;
use PapiAI\Core\Effort;
use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;

/**
 * An agent that can pick up a conversation already under way, instead of starting from a prompt.
 */
interface ContinuesConversationInterface
{
    /**
     * Answer the conversation and append what the run added to it. A run that throws leaves the
     * conversation as it was.
     *
     * @param array{
     *     outputSchema?: Schema,
     *     context?: mixed,
     *     maxTurns?: int,
     *     toolChoice?: string|array{name: string},
     *     effort?: Effort|string,
     * } $options Run options
     */
    public function continue(Conversation $conversation, array $options = []): Response;
}
