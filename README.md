# Papi Core (PHP)

A PHP library for interacting with LLM providers, starting with Claude.

## Requirements

- PHP 8.1 or higher
- Composer

## Setup

1. Clone the repository.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Set up your environment variables. Create a `.env` file in the root directory:
   ```
   ANTHROPIC_API_KEY=your_api_key_here
   ```

## Usage

### Basic Completion with Claude

You can use the `Papi\Providers\ClaudeProvider` to generate text completions.

```php
<?php

require 'vendor/autoload.php';

use Papi\Providers\ClaudeProvider;

$apiKey = getenv('ANTHROPIC_API_KEY');

// Initialize provider
$provider = new ClaudeProvider($apiKey);

// Generate completion
$response = $provider->complete(
    prompt: "Hello, Claude!",
    model: "claude-3-opus-20240229",
    maxTokens: 100
);

echo $response;
```

### Running the Demo

To run the provided example script:

```bash
php examples/demo.php
```
