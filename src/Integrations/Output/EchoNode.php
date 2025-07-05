<?php

namespace Papi\Core\Integrations\Output;

use Papi\Core\Node;

/**
 * EchoNode - Node for output formatting and display
 *
 * Formats and outputs data in various formats (JSON, XML, text)
 * with configurable pretty printing and filtering.
 */
class EchoNode extends Node
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(array $input): array
    {
        $config = $this->config;
        $format = $config['format'] ?? 'json';
        $prettyPrint = $config['pretty_print'] ?? false;

        $startTime = microtime(true);

        try {
            $output = $this->formatOutput($input, $format, $prettyPrint);
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'status' => 'success',
                'data' => $output,
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
