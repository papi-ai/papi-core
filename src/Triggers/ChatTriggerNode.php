<?php

namespace Papi\Core\Triggers;

/**
 * Chat Trigger Node
 *
 * Listens for chat messages and outputs structured chat data.
 * Used for workflows triggered by chat interactions.
 */
class ChatTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'chat';
    }

    protected function processTrigger(): array
    {
        $message = $this->triggerConfig['message'] ?? '';
        $sender = $this->triggerConfig['sender'] ?? 'unknown';
        $channel = $this->triggerConfig['channel'] ?? 'default';

        return [
            'type' => 'chat_message',
            'content' => $message,
            'sender' => $sender,
            'channel' => $channel,
            'timestamp' => time(),
            'metadata' => [
                'trigger_type' => $this->getTriggerType(),
                'node_id' => $this->getId(),
            ]
        ];
    }

    public function validateConfiguration(): bool
    {
        if (empty($this->triggerConfig['message'])) {
            throw new \InvalidArgumentException('Chat trigger requires a message');
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'chat_trigger',
            'trigger_type' => $this->getTriggerType()
        ];
    }
}
