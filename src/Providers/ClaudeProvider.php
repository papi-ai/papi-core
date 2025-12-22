<?php

namespace PapiAi\Core\Providers;

use PapiAi\Core\Providers\LLMProvider;

class ClaudeProvider implements LLMProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: getenv('ANTHROPIC_API_KEY');
    }

    public function complete(string $prompt, array $config = []): string
    {
        $model = $config['model'] ?? 'claude-3-opus-20240229';
        $maxTokens = $config['max_tokens'] ?? 1024;

        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new \Exception('API Error: ' . $result['error']['message']);
        }

        return $result['content'][0]['text'] ?? '';
    }
}
