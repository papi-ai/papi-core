<?php

namespace Papi\Core\Nodes;

/**
 * Tool Capability Interface
 *
 * Nodes that implement this interface can be used as tools by AI agents.
 * Tools provide specific functionality that AI agents can call.
 */
interface Tool
{
    /**
     * Get the tool schema for AI function calling
     */
    public function getToolSchema(): array;

    /**
     * Get the tool name identifier
     */
    public function getToolName(): string;

    /**
     * Get the tool description
     */
    public function getToolDescription(): string;
}
