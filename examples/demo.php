<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PapiAi\Core\Papi;

// Ensure you have ANTHROPIC_API_KEY and OPENAI_API_KEY set in your environment
// or create a .env file and load it (if using vlucas/phpdotenv)

$papi = new Papi('claude');

echo "--- Claude Response ---\n";
try {
    $response = $papi->complete("Tell me a one-sentence joke about Python.");
    echo $response . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- OpenAI Response ---\n";
try {
    $papi->using('openai');
    $response = $papi->complete("Tell me a one-sentence joke about PHP.");
    echo $response . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
