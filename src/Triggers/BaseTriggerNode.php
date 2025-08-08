<?php

namespace Papi\Core\Triggers;

use Papi\Core\Nodes\Node;

/**
 * Base Trigger Node Abstract Class
 *
 * Provides common functionality for all trigger nodes.
 * Triggers are entry points to workflows and cannot accept input.
 */
abstract class BaseTriggerNode implements Node, TriggerInterface
{
    protected array $triggerConfig = [];

    protected string $id;
    protected string $name;

    public function __construct(string $id, string $name, array $config = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->triggerConfig = $config;
        $this->validateConfiguration();
    }

    public function execute(array $input = []): array
    {
        // Triggers don't accept input - they are entry points
        if (!empty($input)) {
            throw new \InvalidArgumentException('Trigger nodes do not accept input');
        }

        return $this->processTrigger();
    }

    abstract protected function processTrigger(): array;

    public function validateConfiguration(): bool
    {
        // Base validation - subclasses can override
        return true;
    }

    public function isReady(): bool
    {
        return $this->validateConfiguration();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
