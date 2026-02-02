<?php

declare(strict_types=1);

namespace PapiAI\Core\Contracts;

use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;

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
     */
    public function addTool(ToolInterface $tool): self;

    /**
     * Get the provider used by this agent.
     */
    public function getProvider(): ProviderInterface;
}
