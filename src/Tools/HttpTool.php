<?php

namespace Papi\Core\Tools;

/**
 * HttpTool - Tool for making HTTP requests
 * 
 * Allows AI agents to fetch data from web APIs and services.
 */
class HttpTool implements ToolInterface
{
    public function getName(): string
    {
        return 'http_request';
    }

    public function getDescription(): string
    {
        return 'Make an HTTP request to fetch data from a URL';
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getParameters(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL to make the request to'
            ],
            'method' => [
                'type' => 'string',
                'description' => 'HTTP method (GET, POST, PUT, DELETE)',
                'default' => 'GET'
            ],
            'headers' => [
                'type' => 'object',
                'description' => 'HTTP headers to include in the request'
            ],
            'body' => [
                'type' => 'string',
                'description' => 'Request body for POST/PUT requests'
            ]
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function execute(array $params): array
    {
        if (!$this->validate($params)) {
            return [
                'success' => false,
                'error' => 'Invalid parameters'
            ];
        }

        $url = $params['url'];
        $method = strtoupper($params['method'] ?? 'GET');
        $headers = $params['headers'] ?? [];
        $body = $params['body'] ?? null;

        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
                CURLOPT_CUSTOMREQUEST => $method,
            ]);

            if ($body && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => "cURL error: $error"
                ];
            }

            if ($httpCode >= 400) {
                return [
                    'success' => false,
                    'error' => "HTTP error: $httpCode",
                    'response' => $response
                ];
            }

            $data = is_string($response) ? json_decode($response, true) : $response;
            if (is_string($response) && json_last_error() !== JSON_ERROR_NONE) {
                $data = $response; // Return as string if not JSON
            }

            return [
                'success' => true,
                'status_code' => $httpCode,
                'data' => $data,
                'raw_response' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function validate(array $params): bool
    {
        if (!isset($params['url']) || empty($params['url'])) {
            return false;
        }

        if (!filter_var($params['url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        if (isset($params['method'])) {
            $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
            if (!in_array(strtoupper($params['method']), $validMethods)) {
                return false;
            }
        }

        return true;
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
