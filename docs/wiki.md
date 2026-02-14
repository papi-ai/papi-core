# Project Overview

**PapiAI Core** is a framework-agnostic, type-safe PHP library designed for building robust AI agents. It abstracts the complexity of interacting with various LLM providers (like Anthropic and Google) while offering powerful features such as tool calling, structured output validation via Zod-like schemas, and event-driven streaming. Built for PHP 8.2+, it serves as a lightweight foundation for integrating AI capabilities into any PHP application, from simple scripts to complex Laravel or Symfony projects.

# Roadmap

### Phase 1: Foundation (Current)
- [x] Core Agent & Tool architecture
- [x] Type-safe Schema validation
- [x] Provider interface abstraction
- [x] Anthropic (Claude) integration
- [x] Google (Gemini) integration
- [x] Event-driven streaming support

### Phase 2: Expansion (Short-term)
- [ ] **OpenAI Integration**: Full support for GPT-4o and other OpenAI models.
- [ ] **Memory Management**: Standardized interfaces for conversation history (Redis, Database adapters).
- [ ] **Resilience**: Built-in retry mechanisms and rate-limit handling.
- [ ] **Documentation**: Comprehensive guides and API references.

### Phase 3: Advanced Capabilities (Mid-term)
- [ ] **Middleware System**: Hooks for logging, cost tracking, and content filtering.
- [ ] **Vector Store Integration**: Native support for RAG (Retrieval-Augmented Generation).
- [ ] **CLI Tooling**: Scaffolding commands for generating agents and tools.
- [ ] **Local LLM Support**: Integration with Ollama and local inference servers.

### Phase 4: Ecosystem (Long-term)
- [ ] **Multi-Agent Orchestration**: Patterns for agents working in teams.
- [ ] **Async Runtime**: Optimized support for Swoole/ReactPHP/Amp.
- [ ] **Visual Builder**: UI tools for designing agent workflows.
