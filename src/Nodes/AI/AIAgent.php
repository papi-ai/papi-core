<?php

namespace Papi\Core\Nodes\AI;

use Papi\Core\Nodes\Node;
use Papi\Core\Nodes\Tool;
use Papi\Core\Nodes\Memory;
use Papi\Core\Integrations\LLMClientInterface;

/**
 * AI Agent Node
 *
 * An AI agent that can use tools and memory through interface-based nodes.
 */
class AIAgent implements Node
{
    private string $id;
    private string $name;
    private string $model = '';
    private string $systemPrompt = '';
    private int $maxTokens = 1000;
    private float $temperature = 0.7;
    private array $toolNodes = [];
    private ?Node $memoryNode = null;
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

        // Get memory context if available
        $context = [];
        if ($this->memoryNode instanceof Memory) {
            $context = $this->memoryNode->getContext();
        }

        // Build tool schemas for AI
        $tools = $this->buildToolSchemas();

        // Generate response
        $response = $this->generateResponse($query, $context, $tools);

        // Add messages to memory if available
        if ($this->memoryNode instanceof Memory) {
            $this->memoryNode->addMessage('user', $query);
            $this->memoryNode->addMessage('assistant', $response);
        }

        return [
            'response' => $response,
            'model' => $this->model,
            'context_used' => count($context),
            'tools_available' => count($tools),
            'metadata' => [
                'node_type' => 'ai_agent',
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

    public function addTool(Node $node): self
    {
        if (!$node instanceof Tool) {
            throw new \InvalidArgumentException('Node must implement Tool interface to be used as a tool');
        }
        $this->toolNodes[] = $node;
        return $this;
    }

    public function setMemory(Node $node): self
    {
        if (!$node instanceof Memory) {
            throw new \InvalidArgumentException('Node must implement Memory interface to be used as memory');
        }
        $this->memoryNode = $node;
        return $this;
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

    private function buildToolSchemas(): array
    {
        return array_map(function (Tool $tool) {
            return $tool->getToolSchema();
        }, $this->toolNodes);
    }

    private function generateResponse(string $query, array $context, array $tools): string
    {
        $client = $this->getLLMClient();

        $messages = $this->buildMessages($query, $context);

        $requestData = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        if (!empty($tools)) {
            $requestData['tools'] = $tools;
        }

        $response = $client->chat($requestData);

        return $this->extractResponse($response);
    }

    private function buildMessages(string $query, array $context): array
    {
        $messages = [];

        if (!empty($this->systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt
            ];
        }

        // Add context messages
        foreach ($context as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }

        // Add current query
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

    private function getLLMClient(): LLMClientInterface
    {
        if ($this->llmClient === null) {
            $this->llmClient = new \Papi\Core\Integrations\RealOpenAIClient();
        }
        return $this->llmClient;
    }

    public function toArray(): array
    {
        $client = $this->llmClient;
        $providerInfo = [
            'provider' => $client ? $client->getProviderName() : 'unknown',
            'supports_tool_calling' => $client ? $client->supportsToolCalling() : false,
            'supported_models' => $client ? $client->getSupportedModels() : []
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'ai_agent',
            'model' => $this->model,
            'system_prompt' => $this->systemPrompt,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'tools_count' => count($this->toolNodes),
            'has_memory' => $this->memoryNode !== null,
            'llm_provider' => $providerInfo
        ];
    }
}
