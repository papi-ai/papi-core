<?php

namespace Papi\Core\Nodes\Utility\Memory;

use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Memory;

/**
 * InMemory Node
 * 
 * Provides in-memory storage for conversation context.
 * Can be used as memory by AI agents.
 */
class InMemory implements Node, Memory
{
    private string $id;
    private string $name;
    private array $messages = [];
    private array $config;
    
    public function __construct(string $id, string $name, array $config = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->config = array_merge([
            'max_messages' => 50,
            'max_tokens' => 4000,
        ], $config);
    }
    
    public function execute(array $input): array
    {
        // Handle memory operations through execute
        $operation = $input['operation'] ?? 'get_context';
        
        return match ($operation) {
            'get_context' => ['context' => $this->getContext()],
            'get_messages' => ['messages' => $this->getMessages()],
            'clear' => $this->clearAndReturn(),
            default => throw new \InvalidArgumentException("Unknown operation: {$operation}")
        };
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function addMessage(string $role, string $content, array $metadata = []): void
    {
        $message = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];
        
        $this->messages[] = $message;
        $this->applyRetentionPolicy();
    }
    
    public function getMessages(?int $limit = null): array
    {
        $messages = $this->messages;
        if ($limit !== null) {
            $messages = array_slice($messages, -$limit);
        }
        return $messages;
    }
    
    public function clear(): void
    {
        $this->messages = [];
    }
    
    public function getContext(int $maxTokens = 4000): array
    {
        $maxTokens = $maxTokens ?: $this->config['max_tokens'];
        $messages = $this->messages;
        $totalTokens = 0;
        $contextMessages = [];
        
        // Start from most recent messages
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = $messages[$i];
            $estimatedTokens = (int) ceil(strlen($message['content']) / 4);
            
            if ($totalTokens + $estimatedTokens > $maxTokens) {
                break;
            }
            
            array_unshift($contextMessages, $message);
            $totalTokens += $estimatedTokens;
        }
        
        return $contextMessages;
    }
    
    private function applyRetentionPolicy(): void
    {
        $maxMessages = $this->config['max_messages'];
        if (count($this->messages) > $maxMessages) {
            $this->messages = array_slice($this->messages, -$maxMessages);
        }
    }
    
    private function clearAndReturn(): array
    {
        $this->clear();
        return ['status' => 'cleared'];
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'in_memory',
            'messages_count' => count($this->messages),
            'max_messages' => $this->config['max_messages'],
            'max_tokens' => $this->config['max_tokens']
        ];
    }
} 
