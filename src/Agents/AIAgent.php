<?php

namespace Papi\Core\Agents;

use Papi\Core\Node;
use Papi\Core\Tools\ToolInterface;
use Papi\Core\Integrations\OpenAIClient;

/**
 * AIAgent - AI-powered workflow node
 * 
 * An AI agent that can call LLMs and execute tools to perform
 * complex tasks within a workflow.
 */
class AIAgent extends Node
{
    private string $model = 'gpt-3.5-turbo';
    private string $systemPrompt = '';
    /** @var ToolInterface[] */
    private array $tools = [];
    private array $memory = [];
    private ?OpenAIClient $openAIClient = null;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(array $input): array
    {
        $startTime = microtime(true);
        
        try {
            // Prepare the context for the AI
            $context = $this->buildContext($input);
            
            // Call the LLM
            $response = $this->callLLM($context);
            
            // Process any tool calls
            $result = $this->processToolCalls($response, $input);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'success',
                'data' => $result,
                'duration' => round($duration, 2),
                'model' => $this->model,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0
            ];
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'duration' => round($duration, 2)
            ];
        }
    }

    /**
     * Set the LLM model to use
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set the system prompt for the AI
     */
    public function setSystemPrompt(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    /**
     * Add a tool that the AI can use
     */
    public function addTool(ToolInterface $tool): self
    {
        $this->tools[] = $tool;
        return $this;
    }

    /**
     * Set the OpenAI client (real or mock)
     */
    public function setOpenAIClient(OpenAIClient $client): self
    {
        $this->openAIClient = $client;
        return $this;
    }

    /**
     * Build the context for the LLM call
     * 
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function buildContext(array $input): array
    {
        $messages = [];
        
        // Add system prompt
        if (!empty($this->systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt
            ];
        }
        
        // Add conversation history from memory
        foreach ($this->memory as $memoryItem) {
            $messages[] = $memoryItem;
        }
        
        // Add current input
        $messages[] = [
            'role' => 'user',
            'content' => $this->formatInput($input)
        ];
        
        return [
            'model' => $this->model,
            'messages' => $messages,
            'tools' => $this->formatTools(),
            'tool_choice' => 'auto'
        ];
    }

    /**
     * Format input for the LLM
     * 
     * @param array<string, mixed> $input
     */
    private function formatInput(array $input): string
    {
        if (isset($input['query'])) {
            return $input['query'];
        }
        if (isset($input['message'])) {
            return $input['message'];
        }
        $json = json_encode($input, JSON_PRETTY_PRINT);
        return $json === false ? '' : $json;
    }

    /**
     * Format tools for the LLM
     * 
     * @return array<int, array<string, mixed>>
     */
    private function formatTools(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $parameters = $tool->getParameters();
            $required = array_keys(array_filter($parameters, function(array $p): bool {
                return isset($p['required']) && $p['required'] === true;
            }));
            $paramSchema = [
                'type' => 'object',
                'properties' => $parameters
            ];
            if (!empty($required)) {
                $paramSchema['required'] = $required;
            }
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $paramSchema
                ]
            ];
        }
        return $tools;
    }

    /**
     * Call the LLM (uses OpenAI client if set, otherwise mock)
     * 
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function callLLM(array $context): array
    {
        if ($this->openAIClient) {
            return $this->openAIClient->chat($context);
        }
        // Mock response for testing/demo
        return [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'This is a mock response. Real OpenAI integration will be implemented.',
                        'tool_calls' => []
                    ]
                ]
            ],
            'usage' => [
                'total_tokens' => 50
            ]
        ];
    }

    /**
     * Process any tool calls from the LLM response
     * 
     * @param array<string, mixed> $response
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function processToolCalls(array $response, array $input): array
    {
        $message = $response['choices'][0]['message'] ?? [];
        $toolCalls = $message['tool_calls'] ?? [];
        $results = [];
        $toolsUsed = [];

        if (!empty($toolCalls)) {
            foreach ($toolCalls as $call) {
                $fn = $call['function']['name'] ?? null;
                $args = isset($call['function']['arguments']) ? json_decode($call['function']['arguments'], true) : [];
                $tool = $this->findTool($fn);
                if ($tool) {
                    $toolResult = $tool->execute($args);
                    $results[] = [
                        'tool' => $fn,
                        'args' => $args,
                        'result' => $toolResult
                    ];
                    $toolsUsed[] = $fn;
                } else {
                    $results[] = [
                        'tool' => $fn,
                        'args' => $args,
                        'error' => 'Tool not found'
                    ];
                }
            }
            return [
                'response' => null,
                'tool_results' => $results,
                'tools_used' => $toolsUsed,
                'input' => $input
            ];
        }
        // No tool calls, just return the content
        return [
            'response' => $message['content'] ?? '',
            'tools_used' => [],
            'input' => $input
        ];
    }

    /**
     * Find a tool by name
     * 
     * @param string $name
     */
    private function findTool(string $name): ?ToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool->getName() === $name) {
                return $tool;
            }
        }
        return null;
    }

    /**
     * Add to conversation memory
     */
    public function addToMemory(string $role, string $content): self
    {
        $this->memory[] = [
            'role' => $role,
            'content' => $content
        ];
        return $this;
    }

    /**
     * Clear conversation memory
     */
    public function clearMemory(): self
    {
        $this->memory = [];
        return $this;
    }
} 
