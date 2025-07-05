<?php

namespace Papi\Core\Integrations\Http;

use Papi\Core\Node;

/**
 * HttpNode - Node for making HTTP requests
 * 
 * Supports GET, POST, PUT, DELETE requests with configurable
 * headers, body, and authentication.
 */
class HttpNode extends Node
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(array $input): array
    {
        $config = $this->config;
        $method = $config['method'] ?? 'GET';
        $url = $config['url'] ?? $input['url'] ?? '';
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? null;

        if (empty($url)) {
            throw new \InvalidArgumentException('URL is required for HTTP node');
        }

        $startTime = microtime(true);
        
        try {
            $response = $this->makeHttpRequest($method, $url, $headers, $body);
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'success',
                'data' => $response,
                'duration' => round($duration, 2)
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
     * @param array<string, string> $headers
     * @param string|array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function makeHttpRequest(string $method, string $url, array $headers = [], $body = null): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ]);

        if ($body && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP error: $httpCode - $response");
        }

        $data = is_string($response) ? json_decode($response, true) : $response;
        if (is_string($response) && json_last_error() !== JSON_ERROR_NONE) {
            $data = $response; // Return as string if not JSON
        }

        return [
            'status_code' => $httpCode,
            'headers' => $headers,
            'body' => $data,
            'raw_response' => $response
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array<int, string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return $formatted;
    }
} 
