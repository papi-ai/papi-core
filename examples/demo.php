<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Papi\Providers\ClaudeProvider;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;

if (!$apiKey) {
    echo "Error: ANTHROPIC_API_KEY not set in .env file.\n";
    exit(1);
}

try {
    // Initialize provider
    $provider = new ClaudeProvider($apiKey);

    echo "Sending request to Claude...\n";

    // Generate completion
    $response = $provider->complete(
        prompt: "Hello, Claude! How are you doing today?",
        model: "claude-3-opus-20240229",
        maxTokens: 100
    );

    echo "\n--- Response ---\n";
    echo $response . "\n";
    echo "----------------\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
