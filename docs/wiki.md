# PapiAI Core - Project Wiki

## Project Overview
PapiAI Core is a framework-agnostic, type-safe PHP 8.2+ library designed to simplify the creation of AI agents. It provides a robust abstraction layer over various LLM providers (currently supporting Anthropic Claude and Google Gemini), enabling developers to build agentic workflows with structured outputs, tool calling, and event-driven streaming without being tied to a specific framework like Laravel or Symfony. The goal is to offer a lightweight yet powerful foundation for integrating AI capabilities into any PHP application.

## Roadmap

### Phase 1: Foundation (Current)
- [x] Core Agent and Tool architecture
- [x] Provider abstractions (Anthropic, Google)
- [x] Type-safe Schema definitions for structured output
- [x] Event-driven streaming support
- [x] Basic observability hooks

### Phase 2: Expansion (Next)
- [ ] OpenAI Provider integration
- [ ] Conversation History and Memory management
- [ ] Middleware support for request/response modification
- [ ] Enhanced error handling and retry mechanisms

### Phase 3: Advanced Features
- [ ] Multi-agent orchestration and delegation
- [ ] Vector database integration for RAG (Retrieval-Augmented Generation)
- [ ] CLI tools for scaffolding agents and tools
- [ ] Pre-built toolkits for common APIs (GitHub, Slack, etc.)
