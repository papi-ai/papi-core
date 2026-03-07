# Papi Core — Development Guidelines

## Project Vision

Papi is the best standalone AI agent library in PHP. Framework-agnostic, minimal dependencies, type-safe.

## Quick Reference

```bash
composer lint          # Check code style (PHP CS Fixer, PSR-12)
composer lint:fix      # Auto-fix code style
composer analyse       # Static analysis (Psalm level 4)
composer test          # Run tests (Pest)
composer test:coverage # Run tests with 75% minimum coverage
composer ci            # Run all checks (lint + analyse + test:coverage)
```

## Code Standards

- **PHP 8.2+** with `declare(strict_types=1)` in every file
- **PSR-12** coding style, enforced by PHP CS Fixer
- **Psalm level 4** static analysis must pass with zero errors
- **75% minimum test coverage**, enforced in CI and pre-commit hook
- **Pest** for testing with describe/it syntax

## Architecture Rules

- **Zero runtime dependencies** — papi-core requires only `php: ^8.2`. No Guzzle, no framework packages.
- **ext-curl only** in provider packages — direct HTTP, no abstraction layers
- **Interface-first** — every new capability starts as a contract in `src/Contracts/`
- **Provider packages are thin** — one main class per provider, converting between core types and API formats
- **Immutable value objects** — Message, Response, ToolCall, StreamChunk, StreamEvent are immutable
- **Static factory methods** — prefer `Type::fromX()` over constructors for public API
- **Enums over constants** — use PHP 8.1+ enums (Role, SchemaType)

## When Adding New Features

1. Define the contract interface in `src/Contracts/`
2. Add value objects for request/response types
3. Write tests first — Pest, describe/it style, in `tests/Unit/`
4. Implement in core with zero external dependencies
5. Provider implementations go in their own packages

## Testing

- Use Pest's `describe()` / `it()` syntax
- Mock providers with Mockery
- Test behavior, not implementation details
- Every public method needs test coverage

## Git Workflow

- Install pre-commit hook: `cp .hooks/pre-commit .git/hooks/pre-commit`
- All checks must pass before committing
- CI runs lint, static analysis, and tests across PHP 8.2-8.5
