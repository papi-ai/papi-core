<?php

namespace Papi\Core\Triggers;

use Papi\Core\Node;

/**
 * Trigger Interface for workflow entry points
 * 
 * Triggers are nodes that can only be used at workflow start.
 * They take no input and output structured data to connected nodes.
 */
interface TriggerInterface
{
    /**
     * Validate trigger configuration
     */
    public function validateConfiguration(): bool;
    
    /**
     * Get trigger type identifier
     */
    public function getTriggerType(): string;
    
    /**
     * Check if trigger is ready to fire
     */
    public function isReady(): bool;
} 
