# Papi Core

A PHP library for interacting with LLM providers (Claude, OpenAI).

## Setup

1. Clone the repository.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Set up your environment variables. Create a `.env` file in the root directory:
   ```
   ANTHROPIC_API_KEY=your_anthropic_key
   OPENAI_API_KEY=your_openai_key
   ```

## Usage

### Basic Usage

```php
use PapiAi\Core\Papi;

$papi = new Papi('claude'); // or 'openai'

$response = $papi->complete("Hello, AI!");
echo $response;
```

### Switching Providers

```php
$papi->using('openai');
$response = $papi->complete("Hello, OpenAI!");
```
