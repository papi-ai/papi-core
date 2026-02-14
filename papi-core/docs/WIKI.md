# PapiAI Project Wiki

## 1. Project Overview

**PapiAI** is a modern, framework-agnostic PHP library designed for building robust AI agents. It provides a unified interface to interact with various Large Language Models (LLMs) like Anthropic Claude and Google Gemini, making it easy to integrate AI capabilities into any PHP application (Laravel, Symfony, or standalone).

### Key Philosophy
- **Simplicity**: Clean, intuitive API for creating agents.
- **Type Safety**: Built on PHP 8.2+ with strict typing.
- **Modularity**: Core logic is separated from specific provider implementations.

## 2. Architecture

The project is structured around a few key abstractions:

### Core Components (`papi-ai/papi-core`)
- **Agent**: The main entry point. It orchestrates interactions between the user, the LLM provider, and available tools.
- **ProviderInterface**: The contract that all LLM providers must implement. It standardizes:
  - `chat()`: Standard request/response.
  - `stream()`: Real-time streaming responses.
  - Capabilities: `supportsTool()`, `supportsVision()`, `supportsStructuredOutput()`.
- **Tool**: encapsulations of functions or class methods that the AI can "call" to perform actions (e.g., fetching weather, querying a database).
- **Schema**: A fluent builder (Zod-like) for defining structured output requirements, ensuring the AI returns data in a predictable format.

### Providers
Specific implementations are handled in separate packages to keep the core lightweight:
- `papi-ai/anthropic`: Adapter for Anthropic's Claude models.
- `papi-ai/google`: Adapter for Google's Gemini models.
- `papi-ai/openai`: (Planned/In-progress) Adapter for OpenAI.

## 3. Getting Started

### Installation
Install the core package and your desired provider:

```bash
composer require papi-ai/core
composer require papi-ai/anthropic
```

### Basic Usage
Here is how to create a simple agent using Claude:

```php
use PapiAI\Core\Agent;
use PapiAI\Anthropic\AnthropicProvider;

$agent = new Agent(
    provider: new AnthropicProvider(apiKey: $_ENV['ANTHROPIC_API_KEY']),
    model: 'claude-3-5-sonnet-20240620',
    instructions: 'You are a helpful coding assistant.'
);

$response = $agent->run('Refactor this class for me...');
echo $response->text;
```

## 4. Advanced Features

- **Tool Calling**: Define PHP functions or class methods as tools. The Agent handles the LLM's request to execute them.
- **Structured Output**: Enforce JSON schemas on responses for reliable data extraction.
- **Streaming**: Subscribe to event streams for real-time UI updates.
- **Observability**: Hooks available for logging and monitoring agent performance.
