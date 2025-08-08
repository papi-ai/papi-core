<?php

namespace Papi\Core\Integrations;

/**
 * Real OpenAI Client Implementation
 * 
 * Concrete implementation of OpenAIClient interface for making
 * actual calls to OpenAI API.
 */
class RealOpenAIClient implements OpenAIClient
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct(string $apiKey = '')
    {
        $this->apiKey = $apiKey ?: getenv('OPENAI_API_KEY') ?: '';
    }
    
    public function chat(array $context): array
    {
        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('OpenAI API key is required');
        }
        
        $url = $this->baseUrl . '/chat/completions';
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        
        $response = $this->makeRequest($url, $context, $headers);
        
        return json_decode($response, true) ?: [];
    }
    
    public function getSupportedModels(): array
    {
        return [
            'gpt-3.5-turbo',
            'gpt-3.5-turbo-16k',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4-turbo-preview'
        ];
    }
    
    public function getProviderName(): string
    {
        return 'openai';
    }
    
    public function supportsToolCalling(): bool
    {
        return true;
    }
    
    private function makeRequest(string $url, array $data, array $headers): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL request failed: {$error}");
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("OpenAI API request failed with HTTP code: {$httpCode}");
        }
        
        return (string) $response;
    }
} 
