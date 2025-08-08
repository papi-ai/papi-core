<?php

namespace Papi\Core\Integrations;

/**
 * Mock OpenAI Client for Testing
 * 
 * Mock implementation of OpenAIClient interface for testing purposes.
 * Returns predefined responses without making actual API calls.
 */
class MockOpenAIClient implements OpenAIClient
{
    private array $responses = [];
    private bool $echoInput = true;
    
    public function __construct(array $responses = [], bool $echoInput = true)
    {
        $this->responses = $responses;
        $this->echoInput = $echoInput;
    }
    
    public function chat(array $context): array
    {
        $userMessage = $this->extractUserMessage($context);
        
        if (isset($this->responses[$userMessage])) {
            $response = $this->responses[$userMessage];
        } elseif ($this->echoInput) {
            $response = $userMessage;
        } else {
            $response = 'Mock response for: ' . $userMessage;
        }
        
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => $response,
                        'role' => 'assistant',
                    ],
                    'finish_reason' => 'stop',
                ]
            ],
            'usage' => [
                'prompt_tokens' => strlen($userMessage) / 4,
                'completion_tokens' => strlen($response) / 4,
                'total_tokens' => (strlen($userMessage) + strlen($response)) / 4,
            ]
        ];
    }
    
    private function extractUserMessage(array $context): string
    {
        $messages = $context['messages'] ?? [];
        
        foreach ($messages as $message) {
            if ($message['role'] === 'user') {
                return $message['content'];
            }
        }
        
        return '';
    }
    
    public function setResponses(array $responses): self
    {
        $this->responses = $responses;
        return $this;
    }
    
    public function setEchoInput(bool $echoInput): self
    {
        $this->echoInput = $echoInput;
        return $this;
    }
    
    public function getSupportedModels(): array
    {
        return [
            'gpt-3.5-turbo',
            'gpt-4',
            'mock-model'
        ];
    }
    
    public function getProviderName(): string
    {
        return 'mock-openai';
    }
    
    public function supportsToolCalling(): bool
    {
        return true;
    }
} 
