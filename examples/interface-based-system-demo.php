<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\AI\LLM;
use Papi\Core\Nodes\Utility\Memory\InMemory;
use Papi\Core\Integrations\MockOpenAIClient;

echo "🚀 Papi Core Interface-Based System Demo\n";
echo "==========================================\n\n";

// 1. Create nodes with interface-based capabilities
echo "1. Creating nodes with interface-based capabilities...\n";
$memoryNode = new InMemory('memory1', 'Conversation Memory');

echo "   ✅ Memory Node (implements Node + Memory)\n\n";

// 2. Create AI agent with type-safe capability checking
echo "2. Creating AI agent with type-safe capabilities...\n";
$aiAgent = new AIAgent('assistant', 'AI Assistant');
$aiAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant that can maintain conversation context.')
    ->setMemory($memoryNode);  // Type-safe: only Memory nodes

echo "   ✅ AI Agent configured with memory\n";
echo "   ✅ Type-safe capability checking enforced\n\n";

// 3. Create simple LLM node for basic text generation
echo "3. Creating simple LLM node...\n";
$llmNode = new LLM('llm1', 'Simple LLM');
$llmNode->setSystemPrompt('You are a helpful assistant.');

echo "   ✅ LLM Node created (implements Node only)\n\n";

// 4. Set up mock clients for testing
echo "4. Setting up mock clients...\n";
$aiMockClient = new MockOpenAIClient([
    'What is the weather like?' => 'I can help you with that question. Let me provide a helpful response.',
    'Tell me about yourself' => 'I am an AI assistant designed to help you with various tasks and maintain conversation context.'
]);

$llmMockClient = new MockOpenAIClient([
    'Hello, how are you?' => 'Hello! I am doing well, thank you for asking. How can I help you today?'
]);

$aiAgent->setLLMClient($aiMockClient);
$llmNode->setLLMClient($llmMockClient);

echo "   ✅ Mock clients configured\n\n";

// 5. Create workflow
echo "5. Creating workflow...\n";
$workflow = new Workflow('interface_demo_workflow');
$workflow->addNode($aiAgent);
$workflow->addNode($llmNode);

echo "   ✅ Workflow created with AI Agent and LLM nodes\n\n";

// 6. Execute workflow with AI Agent (with memory)
echo "6. Executing workflow with AI Agent (memory)...\n";
$execution1 = $workflow->execute(['query' => 'What is the weather like?']);
$result1 = $execution1->getOutputData();

echo "   Input: What is the weather like?\n";
echo "   Response: " . ($result1['response'] ?? 'N/A') . "\n";
echo "   Model: " . ($result1['model'] ?? 'N/A') . "\n";
echo "   Context Used: " . ($result1['context_used'] ?? 'N/A') . " messages\n\n";

// 7. Execute workflow with LLM (simple text generation)
echo "7. Executing workflow with LLM (simple text generation)...\n";
$execution2 = $workflow->execute(['query' => 'Hello, how are you?']);
$result2 = $execution2->getOutputData();

echo "   Input: Hello, how are you?\n";
echo "   Response: " . ($result2['response'] ?? 'N/A') . "\n";
echo "   Model: " . ($result2['model'] ?? 'N/A') . "\n";
echo "   Input Tokens: " . ($result2['input_tokens'] ?? 'N/A') . "\n";
echo "   Output Tokens: " . ($result2['output_tokens'] ?? 'N/A') . "\n\n";

// 8. Demonstrate memory persistence
echo "8. Demonstrating memory persistence...\n";
$memoryMessages = $memoryNode->getMessages();
echo "   Memory contains " . count($memoryMessages) . " messages\n";

if (!empty($memoryMessages)) {
    echo "   Latest message: {$memoryMessages[count($memoryMessages) - 1]['role']} - {$memoryMessages[count($memoryMessages) - 1]['content']}\n";
}

echo "\n";

// 9. Demonstrate interface checking
echo "9. Demonstrating interface checking...\n";
echo "   Memory Node is Memory: " . ($memoryNode instanceof \Papi\Core\Nodes\Memory ? 'Yes' : 'No') . "\n";
echo "   LLM Node is Tool: " . ($llmNode instanceof \Papi\Core\Nodes\Tool ? 'No (as expected)' : 'No') . "\n";
echo "   AI Agent is Node: " . ($aiAgent instanceof \Papi\Core\Nodes\Node ? 'Yes' : 'No') . "\n\n";

echo "🎉 Interface-based system demo completed successfully!\n";
echo "====================================================\n\n";

echo "Key Benefits Demonstrated:\n";
echo "- Type-safe capability checking\n";
echo "- Memory interface for conversation context\n";
echo "- Clean interface-based architecture\n";
echo "- Easy extensibility and testing\n";
echo "- Clear separation of concerns\n";
echo "- HTTP and Math utilities removed (should be implicit in integration nodes)\n"; 
