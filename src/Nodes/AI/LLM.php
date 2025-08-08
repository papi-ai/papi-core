<?php

namespace Papi\Core\Nodes\AI;

use Papi\Core\Nodes\Node;
use Papi\Core\Integrations\LLMClientInterface;

/**
 * LLM Node
 * 
 * Simple LLM node for basic text generation without tool-calling capabilities.
 */
class LLM implements Node
{
    private string $id;
    private string $name;
    private string $model = '';
    private string $systemPrompt = '';
    private int $maxTokens = 1000;
    private float $temperature = 0.7;
    private ?LLMClientInterface $llmClient = null;
    
    public function __construct(string $id, string $name, array $config = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->configure($config);
    }
    
    public function execute(array $input): array
    {
        $query = $this->extractQuery($input);
        $response = $this->generateResponse($query);
        
        return [
            'response' => $response,
            'model' => $this->model,
            'input_tokens' => $this->estimateTokens($query),
            'output_tokens' => $this->estimateTokens($response),
            'metadata' => [
                'node_type' => 'llm',
                'node_id' => $this->getId(),
            ]
        ];
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    public function setSystemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }
    
    public function setMaxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }
    
    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }
    
    public function setLLMClient(LLMClientInterface $client): self
    {
        $this->llmClient = $client;
        return $this;
    }
    
    private function configure(array $config): void
    {
        $this->model = $config['model'] ?? $this->model;
        $this->systemPrompt = $config['system_prompt'] ?? $this->systemPrompt;
        $this->maxTokens = $config['max_tokens'] ?? $this->maxTokens;
        $this->temperature = $config['temperature'] ?? $this->temperature;
    }
    
    private function extractQuery(array $input): string
    {
        return $input['query'] ?? $input['message'] ?? $input['text'] ?? '';
    }
    
    private function generateResponse(string $query): string
    {
        $client = $this->getLLMClient();
        
        $messages = $this->buildMessages($query);
        
        $requestData = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];
        
        $response = $client->chat($requestData);
        
        return $this->extractResponse($response);
    }
    
    private function buildMessages(string $query): array
    {
        $messages = [];
        
        if (!empty($this->systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $query
        ];
        
        return $messages;
    }
    
    private function extractResponse(array $response): string
    {
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return 'No response generated';
    }
    
    private function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }
    
    private function getLLMClient(): LLMClientInterface
    {
        if ($this->llmClient === null) {
            $this->llmClient = new \Papi\Core\Integrations\RealOpenAIClient();
        }
        return $this->llmClient;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'llm',
            'model' => $this->model,
            'system_prompt' => $this->systemPrompt,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature
        ];
    }
} 
