<?php

namespace Papi\Core\Nodes;

/**
 * Trigger Capability Interface
 * 
 * Nodes that implement this interface can be used as workflow triggers.
 * Trigger nodes are entry points to workflows and don't accept input.
 */
interface Trigger
{
    /**
     * Get the trigger type identifier
     */
    public function getTriggerType(): string;
    
    /**
     * Validate trigger configuration
     */
    public function validateConfiguration(): bool;
    
    /**
     * Check if trigger is ready to fire
     */
    public function isReady(): bool;
} 
