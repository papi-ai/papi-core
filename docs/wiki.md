# PapiAI Project Wiki

## Project Overview

PapiAI is a powerful, framework-agnostic PHP library designed for building robust AI agents. It provides a type-safe, fluent interface for interacting with Large Language Models (LLMs) from multiple providers. 

At its core, PapiAI abstracts the complexity of different AI APIs, offering a unified way to handle:
- **Tool Calling:** Define tools as simple functions or class methods.
- **Structured Output:** Enforce strict JSON schemas on model responses.
- **Streaming:** Real-time response streaming with event hooks.
- **Provider Flexibility:** Swap between Anthropic, Google Gemini, and others without rewriting application logic.

Designed for modern PHP (8.2+), PapiAI fits seamlessly into Laravel, Symfony, or standalone scripts, making it the ideal foundation for building chatbots, data extraction pipelines, and autonomous agents.

## Roadmap

### Phase 1: Foundation (Current)
- [x] Core Agent Architecture (Agent, Tool, Schema)
- [x] Provider Interfaces
- [x] Anthropic (Claude) Integration
- [x] Google (Gemini) Integration
- [x] Tool Calling & Structured Output
- [x] Basic Event Streaming

### Phase 2: Expansion (Next Up)
- [ ] **OpenAI Provider:** Full support for GPT-4o and o1 models.
- [ ] **Memory System:** Built-in conversation history management (short-term & long-term).
- [ ] **Middleware/Hooks:** Deeper observability for logging and monitoring agent actions.

### Phase 3: Advanced Capabilities
- [ ] **RAG Support:** Native document loading and vector store integration.
- [ ] **Multi-Agent Orchestration:** Patterns for agents to communicate and delegate tasks.
- [ ] **CLI Tool:** Helper commands for generating agents and tools.

### Phase 4: Ecosystem
- [ ] **Laravel Bundle:** Dedicated service provider and facades for Laravel.
- [ ] **Symfony Bundle:** Dependency injection configuration for Symfony.
- [ ] **Community Plugin System:** Easier sharing of custom tools and providers.
