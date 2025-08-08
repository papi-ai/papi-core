<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\Utility\Output;
use Papi\Core\Integrations\MockOpenAIClient;

echo "🚀 Papi Core Output Node Demo\n";
echo "=============================\n\n";

// Create AI agent
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that provides information about various topics.');

// Set up mock client
$mockClient = new MockOpenAIClient([
    'Tell me about PHP' => 'PHP is a popular server-side scripting language designed for web development.'
]);
$aiAgent->setLLMClient($mockClient);

// Create different output nodes for different formats
$jsonOutput = new Output('json_output', 'JSON Output', [
    'format' => 'json',
    'pretty_print' => true
]);

$xmlOutput = new Output('xml_output', 'XML Output', [
    'format' => 'xml',
    'pretty_print' => true
]);

$textOutput = new Output('text_output', 'Text Output', [
    'format' => 'text'
]);

$csvOutput = new Output('csv_output', 'CSV Output', [
    'format' => 'csv'
]);

// Create workflow
$workflow = new Workflow('output_demo_workflow');
$workflow->addNode($aiAgent);
$workflow->addNode($jsonOutput);
$workflow->addNode($xmlOutput);
$workflow->addNode($textOutput);
$workflow->addNode($csvOutput);

// Connect AI agent to all output nodes
$workflow->addConnection(new Connection('assistant', 'json_output'));
$workflow->addConnection(new Connection('assistant', 'xml_output'));
$workflow->addConnection(new Connection('assistant', 'text_output'));
$workflow->addConnection(new Connection('assistant', 'csv_output'));

// Execute workflow
$execution = $workflow->execute(['query' => 'Tell me about PHP']);
$result = $execution->getOutputData();

echo "📊 Workflow Results:\n";
echo "===================\n\n";

// Display results from different output nodes
foreach ($result as $nodeId => $nodeResult) {
    if (str_contains($nodeId, 'output')) {
        echo "🔸 {$nodeId}:\n";
        echo "   Status: {$nodeResult['status']}\n";
        echo "   Duration: {$nodeResult['duration']}ms\n";
        echo "   Format: {$nodeResult['metadata']['format']}\n";
        echo "   Data:\n";
        echo "   " . str_replace("\n", "\n   ", $nodeResult['data']) . "\n\n";
    }
}

echo "🎉 Output node demo completed successfully!\n";
echo "==========================================\n\n";

echo "Key Features Demonstrated:\n";
echo "- Multiple output formats (JSON, XML, Text, CSV)\n";
echo "- Pretty printing for readable output\n";
echo "- Metadata inclusion for debugging\n";
echo "- Performance timing for each format\n";
echo "- Error handling and graceful fallbacks\n"; 
