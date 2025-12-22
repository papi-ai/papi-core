<?php

namespace Papi\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ClaudeProvider
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private string $version = '2023-06-01';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
                'content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate a completion using Claude.
     *
     * @param string $prompt The prompt to send to Claude.
     * @param string $model The model to use (default: claude-3-opus-20240229).
     * @param int $maxTokens The maximum number of tokens to generate.
     * @return string The generated text.
     * @throws \Exception If the API call fails.
     */
    public function complete(string $prompt, string $model = "claude-3-opus-20240229", int $maxTokens = 1024): string
    {
        try {
            $response = $this->client->post('/v1/messages', [
                'json' => [
                    'model' => $model,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['content'][0]['text'])) {
                return $body['content'][0]['text'];
            }

            throw new \Exception("Unexpected response format from Claude API.");

        } catch (GuzzleException $e) {
            throw new \Exception("Claude API request failed: " . $e->getMessage());
        }
    }
}
