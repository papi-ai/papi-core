<?php

namespace Papi\Core\Triggers;

/**
 * Email Trigger Node
 * 
 * Listens for email notifications and outputs structured email data.
 * Used for workflows triggered by email events.
 */
class EmailTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'email';
    }
    
    protected function processTrigger(): array
    {
        $subject = $this->triggerConfig['subject'] ?? '';
        $body = $this->triggerConfig['body'] ?? '';
        $sender = $this->triggerConfig['sender'] ?? '';
        $recipients = $this->triggerConfig['recipients'] ?? [];
        
        return [
            'type' => 'email',
            'subject' => $subject,
            'body' => $body,
            'sender' => $sender,
            'recipients' => $recipients,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function validateConfiguration(): bool
    {
        if (empty($this->triggerConfig['subject']) && empty($this->triggerConfig['body'])) {
            throw new \InvalidArgumentException('Email trigger requires subject or body');
        }
        
        return true;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'email_trigger',
            'trigger_type' => $this->getTriggerType()
        ];
    }
} 
