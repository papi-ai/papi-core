<?php

namespace Papi\Core\Nodes;

/**
 * Base Node Interface
 *
 * All nodes in Papi Core implement this interface.
 * Nodes can also implement additional capability interfaces (Tool, Memory, Trigger).
 */
interface Node
{
    /**
     * Execute the node with input data
     */
    public function execute(array $input): array;

    /**
     * Get the unique identifier for this node
     */
    public function getId(): string;

    /**
     * Get the display name for this node
     */
    public function getName(): string;

    /**
     * Convert node to array representation
     */
    public function toArray(): array;
}
