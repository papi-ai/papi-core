# PapiAI Project Wiki

## Project Overview
PapiAI Core is a professional, type-safe PHP library designed for building robust AI agents. It decouples the agent logic from specific LLM providers, allowing developers to switch between Anthropic (Claude), Google (Gemini), and others with ease. It focuses on developer experience with strict typing (PHP 8.2+), intuitive interfaces for tool calling, and structured data extraction.

## Architecture
The library is built around a few key components:
- **Agent**: The main orchestrator that manages context, instructions, and the conversation loop.
- **Provider**: An interface for LLM backends (e.g., `AnthropicProvider`, `GoogleProvider`).
- **Tool**: Encapsulates capabilities the agent can use, definable via closures or class attributes.
- **Schema**: A fluent builder for defining structured output expectations (Zod-like).

## Roadmap

### Phase 1: Foundation (Current)
- Core architecture (Agent, Provider, Tool, Schema).
- Support for Anthropic Claude and Google Gemini.
- Event-driven streaming and observability hooks.

### Phase 2: Expansion (Next)
- **OpenAI Integration**: Add support for GPT-4o and o1 models.
- **Memory Systems**: Abstract persistence layers for conversation history (Redis, SQL).
- **RAG Support**: Simple interfaces for vector store retrieval.

### Phase 3: Advanced Capabilities
- **Multi-Agent Systems**: Patterns for agent delegation and collaboration.
- **Framework Integrations**: Dedicated packages for Laravel and Symfony.
- **Local LLM Support**: Adapters for Ollama/Llama.cpp.
