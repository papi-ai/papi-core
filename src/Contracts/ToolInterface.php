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

interface ToolInterface
{
    /**
     * Get the tool name (used in API calls).
     */
    public function getName(): string;

    /**
     * Get the tool description for the LLM.
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
}
