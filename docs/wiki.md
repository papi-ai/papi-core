# Project Overview

**PapiAI Core** is a framework-agnostic, type-safe PHP (8.2+) library designed for building robust AI agents. It serves as a foundational layer that abstracts the complexities of interacting with various LLM providers while offering powerful features like:

*   **Multi-Provider Support:** Seamless integration with Anthropic (Claude) and Google (Gemini), with more on the way.
*   **Tool Calling:** flexible definition of tools using closures or class-based attributes.
*   **Structured Output:** Strong validation of LLM responses using a Zod-like schema builder.
*   **Event-Driven Streaming:** First-class support for streaming responses and events.
*   **Observability:** Built-in hooks for logging, metrics, and error handling.

It is designed to be lightweight and easily embeddable into any PHP project, whether it's a standalone script, a Laravel application, or a Symfony service.

# Roadmap

## Phase 1: Foundation (Completed)
*   [x] Core Agent and Provider architecture
*   [x] Anthropic and Google provider implementations
*   [x] Tool calling and execution logic
*   [x] Structured output parsing and validation
*   [x] Basic event-driven streaming

## Phase 2: Capabilities & Resilience (Short-term)
*   [ ] **OpenAI Integration:** Add support for GPT-4 and other OpenAI models.
*   [ ] **Memory Management:** Implement flexible memory stores (Redis, Database) to maintain conversation context.
*   [ ] **Resilience:** Add built-in retry mechanisms, rate-limiting handling, and circuit breakers.

## Phase 3: Expansion (Mid-term)
*   [ ] **Middleware Pipeline:** Allow intercepting and modifying requests/responses globally.
*   [ ] **RAG Integration:** Native support for Vector Stores to enable Retrieval-Augmented Generation.
*   [ ] **CLI Tooling:** A dedicated CLI for scaffolding agents and testing prompts.

## Phase 4: Advanced Orchestration (Long-term)
*   [ ] **Multi-Agent Systems:** Patterns and tools for coordinating multiple agents working together.
*   [ ] **Async Runtime:** Better support for asynchronous execution environments (e.g., Swoole, Amphp).
