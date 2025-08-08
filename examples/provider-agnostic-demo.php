<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Papi\Core\Workflow;
use Papi\Core\Connection;
use Papi\Core\Nodes\AI\AIAgent;
use Papi\Core\Nodes\AI\LLM;
use Papi\Core\Nodes\Utility\Output;
use Papi\Core\Integrations\MockOpenAIClient;
use Papi\Core\Integrations\LLMClientInterface;

echo "🚀 Papi Core Provider-Agnostic AI Demo\n";
echo "=====================================\n\n";

// Create a custom LLM client that implements LLMClientInterface
class CustomLLMClient implements LLMClientInterface
{
    private string $providerName;
    private array $responses;
    
    public function __construct(string $providerName, array $responses = [])
    {
        $this->providerName = $providerName;
        $this->responses = $responses;
    }
    
    public function chat(array $requestData): array
    {
        $userMessage = $this->extractUserMessage($requestData);
        $model = $requestData['model'] ?? 'custom-model';
        
        $response = $this->responses[$userMessage] ?? "Response from {$this->providerName} for: {$userMessage}";
        
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => $response,
                        'role' => 'assistant',
                    ],
                    'finish_reason' => 'stop',
                ]
            ],
            'usage' => [
                'prompt_tokens' => strlen($userMessage) / 4,
                'completion_tokens' => strlen($response) / 4,
                'total_tokens' => (strlen($userMessage) + strlen($response)) / 4,
            ]
        ];
    }
    
    public function getSupportedModels(): array
    {
        return [
            'custom-model',
            'custom-model-plus',
            'custom-model-pro'
        ];
    }
    
    public function getProviderName(): string
    {
        return $this->providerName;
    }
    
    public function supportsToolCalling(): bool
    {
        return true;
    }
    
    private function extractUserMessage(array $context): string
    {
        $messages = $context['messages'] ?? [];
        
        foreach ($messages as $message) {
            if ($message['role'] === 'user') {
                return $message['content'];
            }
        }
        
        return '';
    }
}

echo "1. Creating different LLM providers...\n";

// Create different LLM clients
$openAIClient = new MockOpenAIClient([
    'Tell me about AI' => 'AI is a field of computer science focused on creating intelligent machines.'
]);

$anthropicClient = new CustomLLMClient('anthropic', [
    'Tell me about AI' => 'Artificial Intelligence is the simulation of human intelligence in machines.'
]);

$customClient = new CustomLLMClient('custom-provider', [
    'Tell me about AI' => 'AI represents the future of computing and automation.'
]);

echo "   ✅ OpenAI Mock Client\n";
echo "   ✅ Anthropic Custom Client\n";
echo "   ✅ Custom Provider Client\n\n";

echo "2. Creating AI agents with different providers...\n";

// Create AI agents with different providers
$openAIAgent = new AIAgent('openai_agent', 'OpenAI Agent');
$openAIAgent->setModel('gpt-3.5-turbo')
    ->setSystemPrompt('You are a helpful assistant.')
    ->setLLMClient($openAIClient);

$anthropicAgent = new AIAgent('anthropic_agent', 'Anthropic Agent');
$anthropicAgent->setModel('claude-3-sonnet')
    ->setSystemPrompt('You are a helpful assistant.')
    ->setLLMClient($anthropicClient);

$customAgent = new AIAgent('custom_agent', 'Custom Provider Agent');
$customAgent->setModel('custom-model')
    ->setSystemPrompt('You are a helpful assistant.')
    ->setLLMClient($customClient);

echo "   ✅ AI Agent with OpenAI\n";
echo "   ✅ AI Agent with Anthropic\n";
echo "   ✅ AI Agent with Custom Provider\n\n";

echo "3. Creating LLM nodes with different providers...\n";

// Create LLM nodes with different providers
$openAILLM = new LLM('openai_llm', 'OpenAI LLM');
$openAILLM->setModel('gpt-4')
    ->setSystemPrompt('You are a helpful assistant.')
    ->setLLMClient($openAIClient);

$anthropicLLM = new LLM('anthropic_llm', 'Anthropic LLM');
$anthropicLLM->setModel('claude-3-opus')
    ->setSystemPrompt('You are a helpful assistant.')
    ->setLLMClient($anthropicClient);

echo "   ✅ LLM Node with OpenAI\n";
echo "   ✅ LLM Node with Anthropic\n\n";

echo "4. Creating output nodes...\n";

$jsonOutput = new Output('json_output', 'JSON Output', [
    'format' => 'json',
    'pretty_print' => true
]);

echo "   ✅ JSON Output Node\n\n";

echo "5. Creating workflow with multiple providers...\n";

$workflow = new Workflow('multi_provider_workflow');
$workflow->addNode($openAIAgent);
$workflow->addNode($anthropicAgent);
$workflow->addNode($customAgent);
$workflow->addNode($openAILLM);
$workflow->addNode($anthropicLLM);
$workflow->addNode($jsonOutput);

// Connect all agents to output
$workflow->addConnection(new Connection('openai_agent', 'json_output'));
$workflow->addConnection(new Connection('anthropic_agent', 'json_output'));
$workflow->addConnection(new Connection('custom_agent', 'json_output'));

echo "   ✅ Workflow created with multiple providers\n\n";

echo "6. Executing workflow with different providers...\n";

$execution = $workflow->execute(['query' => 'Tell me about AI']);
$result = $execution->getOutputData();

echo "   📊 Results from different providers:\n\n";

foreach ($result as $nodeId => $nodeResult) {
    if (str_contains($nodeId, 'agent') || str_contains($nodeId, 'llm')) {
        $provider = $nodeResult['metadata']['node_type'] ?? 'unknown';
        $response = $nodeResult['response'] ?? 'No response';
        echo "   🔸 {$nodeId} ({$provider}):\n";
        echo "      {$response}\n\n";
    }
}

echo "7. Demonstrating provider information...\n";

$agents = [$openAIAgent, $anthropicAgent, $customAgent];

foreach ($agents as $agent) {
    $agentArray = $agent->toArray();
    $providerInfo = $agentArray['llm_provider'];
    
    echo "   🔸 {$agentArray['name']}:\n";
    echo "      Provider: {$providerInfo['provider']}\n";
    echo "      Supports Tool Calling: " . ($providerInfo['supports_tool_calling'] ? 'Yes' : 'No') . "\n";
    echo "      Supported Models: " . implode(', ', $providerInfo['supported_models']) . "\n\n";
}

echo "🎉 Provider-agnostic demo completed successfully!\n";
echo "===============================================\n\n";

echo "Key Benefits Demonstrated:\n";
echo "- AIAgent is now provider-agnostic\n";
echo "- Easy to switch between different LLM providers\n";
echo "- Clean interface-based architecture\n";
echo "- No tight coupling to OpenAI\n";
echo "- Easy to add new LLM providers\n";
echo "- Consistent API across all providers\n"; 
