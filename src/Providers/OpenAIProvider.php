<?php

namespace PapiAi\Core\Providers;

class OpenAIProvider implements LLMProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: getenv('OPENAI_API_KEY');
    }

    public function complete(string $prompt, array $config = []): string
    {
        $model = $config['model'] ?? 'gpt-4o';
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
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
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

        return $result['choices'][0]['message']['content'] ?? '';
    }
}
