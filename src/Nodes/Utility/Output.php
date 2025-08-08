<?php

namespace Papi\Core\Nodes\Utility;

use Papi\Core\Nodes\Node;

/**
 * Output Node
 * 
 * Formats and outputs workflow data in various formats (JSON, XML, text, array).
 * Serves as an end node for workflows to format and display results.
 */
class Output implements Node
{
    private string $id;
    private string $name;
    private array $config;
    
    public function __construct(string $id, string $name, array $config = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->config = array_merge([
            'format' => 'json',
            'pretty_print' => false,
            'include_metadata' => true
        ], $config);
    }
    
    public function execute(array $input): array
    {
        $format = $this->config['format'] ?? 'json';
        $prettyPrint = $this->config['pretty_print'] ?? false;
        $includeMetadata = $this->config['include_metadata'] ?? true;

        $startTime = microtime(true);

        try {
            $output = $this->formatOutput($input, $format, $prettyPrint);
            $duration = (microtime(true) - $startTime) * 1000;

            $result = [
                'status' => 'success',
                'data' => $output,
                'duration' => round($duration, 2)
            ];
            
            if ($includeMetadata) {
                $result['metadata'] = [
                    'node_type' => 'output',
                    'node_id' => $this->getId(),
                    'format' => $format,
                    'pretty_print' => $prettyPrint
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'duration' => round($duration, 2),
                'metadata' => [
                    'node_type' => 'output',
                    'node_id' => $this->getId()
                ]
            ];
        }
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'output',
            'format' => $this->config['format'] ?? 'json',
            'pretty_print' => $this->config['pretty_print'] ?? false,
            'include_metadata' => $this->config['include_metadata'] ?? true
        ];
    }
    
    /**
     * @param array<string, mixed> $input
     */
    private function formatOutput(array $input, string $format, bool $prettyPrint): mixed
    {
        switch (strtolower($format)) {
            case 'json':
                return $this->formatAsJson($input, $prettyPrint);
            case 'xml':
                return $this->formatAsXml($input, $prettyPrint);
            case 'text':
                return $this->formatAsText($input);
            case 'array':
                return $input;
            case 'csv':
                return $this->formatAsCsv($input);
            default:
                return $input;
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function formatAsJson(array $input, bool $prettyPrint): string
    {
        $options = JSON_UNESCAPED_SLASHES;
        if ($prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($input, $options);
        return $json === false ? '' : $json;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function formatAsXml(array $input, bool $prettyPrint): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
        $this->arrayToXml($input, $xml);
        $asXml = $xml->asXML();
        if ($asXml === false) {
            $asXml = '<root/>';
        }
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = $prettyPrint;
        $dom->loadXML($asXml);
        $xmlString = $dom->saveXML();
        return $xmlString === false ? '' : $xmlString;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function formatAsText(array $input): string
    {
        $output = '';
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output .= "$key: " . json_encode($value) . "\n";
            } else {
                $output .= "$key: $value\n";
            }
        }
        return trim($output);
    }
    
    /**
     * @param array<string, mixed> $input
     */
    private function formatAsCsv(array $input): string
    {
        if (empty($input)) {
            return '';
        }
        
        // Handle flat arrays
        if (!is_array(reset($input))) {
            $headers = array_keys($input);
            $values = array_values($input);
            return implode(',', $headers) . "\n" . implode(',', $values);
        }
        
        // Handle arrays of arrays
        $headers = array_keys(reset($input));
        $csv = implode(',', $headers) . "\n";
        
        foreach ($input as $row) {
            $values = [];
            foreach ($headers as $header) {
                $values[] = $row[$header] ?? '';
            }
            $csv .= implode(',', $values) . "\n";
        }
        
        return trim($csv);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }
} 
