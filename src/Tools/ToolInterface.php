<?php

namespace Papi\Core\Tools;

/**
 * ToolInterface - Contract for tools that AI agents can use
 *
 * Tools are functions that AI agents can call to perform specific tasks
 * like making HTTP requests, performing calculations, or accessing data.
 */
interface ToolInterface
{
    /**
     * Get the name of the tool
     */
    public function getName(): string;

    /**
     * Get a description of what the tool does
     */
    public function getDescription(): string;

    /**
     * Get the parameters that the tool accepts
     *
     * @return array<string, array<string, string|bool>> Parameter definitions with type, description, required, etc.
     */
    public function getParameters(): array;

    /**
     * Execute the tool with the given parameters
     *
     * @param array<string, mixed> $params The parameters to pass to the tool
     * @return array<string, mixed> The result of the tool execution
     */
    public function execute(array $params): array;

    /**
     * Validate the parameters before execution
     *
     * @param array<string, mixed> $params The parameters to validate
     * @return bool True if parameters are valid
     */
    public function validate(array $params): bool;
}
