<?php

namespace Papi\Core\Triggers;

/**
 * Manual Trigger Node
 * 
 * Handles manual workflow triggers and outputs structured query data.
 * Used for workflows triggered by manual user input.
 */
class ManualTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'manual';
    }
    
    protected function processTrigger(): array
    {
        $query = $this->triggerConfig['query'] ?? '';
        $user = $this->triggerConfig['user'] ?? 'unknown';
        
        return [
            'type' => 'manual_trigger',
            'query' => $query,
            'user' => $user,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        // Manual triggers can have empty configuration
        return true;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'manual_trigger',
            'trigger_type' => $this->getTriggerType()
        ];
    }
} 
