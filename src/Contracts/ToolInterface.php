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

/**
 * Contract for tools that can be invoked by an LLM agent.
 *
 * Tools expose a name, description, and parameter schema so the LLM can decide
 * when and how to call them. Implementations must be serialisable to both
 * Anthropic and OpenAI API formats.
 */
interface ToolInterface
{
    /**
     * Get the tool name (used in API calls).
     *
     * @return string A snake_case identifier unique within the agent's tool set
     */
    public function getName(): string;

    /**
     * Get the tool description for the LLM.
     *
     * @return string A human-readable description the LLM uses to decide when to invoke this tool
     */
    public function getDescription(): string;

    /**
     * Get the JSON schema for the tool parameters.
     *
     * @return array{type: string, properties?: array, required?: array<string>}
     */
    public function getParameterSchema(): array;

    /**
     * Execute the tool with the given arguments.
     *
     * @param array<string, mixed> $arguments The arguments from the LLM
     * @param mixed $context Optional context/dependencies
     * @return mixed The tool result
     */
    public function execute(array $arguments, mixed $context = null): mixed;

    /**
     * Convert to Anthropic API tool format.
     *
     * @return array{name: string, description: string, input_schema: array}
     */
    public function toAnthropic(): array;

    /**
     * Convert to OpenAI API tool format.
     *
     * @return array{type: string, function: array{name: string, description: string, parameters: array}}
     */
    public function toOpenAI(): array;
}
