# PapiAI Project Wiki

## Project Overview

**PapiAI Core** is a robust, type-safe PHP library designed for building AI agents. It provides a unified interface for interacting with various Large Language Models (LLMs) while handling the complexities of tool calling, structured output, and conversation state management.

The library is framework-agnostic, meaning it can be dropped into any PHP 8.2+ project, whether it's a standalone script, a Laravel application, or a Symfony microservice.

### Key Concepts

*   **Agent**: The central orchestrator that manages instructions, tools, and the provider connection.
*   **Provider**: An abstraction layer for AI models (currently supporting Anthropic and Google Gemini).
*   **Tool**: Capabilities given to the agent, defined as simple functions or class methods with attributes.
*   **Schema**: A Zod-like fluent interface for defining structured output requirements.
*   **Stream**: First-class support for real-time streaming of text and tool execution events.

## Roadmap

This roadmap outlines the development phases for PapiAI, moving from the current foundational state to a full-featured AI ecosystem.

### Phase 1: Foundation (Current Status)
*   [x] Core `Agent` logic and conversation management.
*   [x] Provider interfaces for Anthropic (Claude) and Google (Gemini).
*   [x] Function and Class-based Tool definitions.
*   [x] Structured output validation using Schemas.
*   [x] Event-based streaming support.
*   [x] Basic test suite with Pest.

### Phase 2: Expansion & Integration
*   [ ] **OpenAI Provider**: Add support for GPT-4o and o1 models.
*   [ ] **Memory Management**: Implement interfaces for short-term and long-term memory (Redis, Vector DBs).
*   [ ] **Middleware Pipelines**: Allow intercepting requests/responses for logging, moderation, or modification.
*   [ ] **Http Client Abstraction**: Decouple from specific HTTP clients to allow deeper customization.

### Phase 3: Developer Experience
*   [ ] **Laravel Bundle**: dedicated service providers, facades, and artisan commands.
*   [ ] **Symfony Bundle**: Dependency injection configuration and debug toolbar integration.
*   [ ] **CLI Mode**: Run agents directly from the command line for testing and automation.
*   [ ] **Documentation Site**: Comprehensive static site with API references and cookbooks.

### Phase 4: Advanced Features
*   [ ] **Multi-Agent Orchestration**: Patterns for agents to communicate and delegate tasks to one another.
*   [ ] **Evaluations**: Tools for measuring agent performance and accuracy against test sets.
*   [ ] **Observability Dashboard**: specialized UI for tracing agent thoughts and tool executions.
