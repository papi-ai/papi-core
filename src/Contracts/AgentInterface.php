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

use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\StreamChunk;
use PapiAI\Core\StreamEvent;

/**
 * Contract for AI agents that orchestrate LLM interactions and tool execution.
 *
 * An agent manages the agentic loop: sending prompts, processing tool calls,
 * and returning final responses. Supports both synchronous and streaming modes.
 */
interface AgentInterface
{
    /**
     * Run the agent with a prompt and return the response.
     *
     * @param string $prompt The user prompt
     * @param array{
     *     outputSchema?: Schema,
     *     context?: mixed,
     *     maxTurns?: int,
     * } $options Run options
     */
    public function run(string $prompt, array $options = []): Response;

    /**
     * Stream the agent response as text chunks.
     *
     * @param string $prompt The user prompt
     * @param array $options Run options
     * @return iterable<StreamChunk>
     */
    public function stream(string $prompt, array $options = []): iterable;

    /**
     * Stream the agent response with detailed events.
     *
     * @param string $prompt The user prompt
     * @param array $options Run options
     * @return iterable<StreamEvent>
     */
    public function streamEvents(string $prompt, array $options = []): iterable;

    /**
     * Add a tool to the agent.
     *
     * @param ToolInterface $tool The tool to register
     *
     * @return self For method chaining
     */
    public function addTool(ToolInterface $tool): self;

    /**
     * Get the provider used by this agent.
     *
     * @return ProviderInterface The underlying LLM provider
     */
    public function getProvider(): ProviderInterface;
}
