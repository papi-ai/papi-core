# Installation

Install the core package and the provider(s) you need:

```bash
composer require papi-ai/papi-core

# Pick your provider(s)
composer require papi-ai/anthropic   # Claude
composer require papi-ai/openai      # GPT-4o, o1
composer require papi-ai/google      # Gemini
composer require papi-ai/ollama      # Local models
composer require papi-ai/mistral     # Mistral
composer require papi-ai/groq        # Groq LPU
composer require papi-ai/grok        # xAI Grok
composer require papi-ai/deepseek    # DeepSeek
composer require papi-ai/cohere      # Cohere
composer require papi-ai/azure-openai # Azure OpenAI
```

For text-to-speech:

```bash
composer require papi-ai/elevenlabs  # ElevenLabs TTS
```

Framework bridges, if you are not using Papi standalone:

```bash
composer require papi-ai/laravel     # Laravel service provider, config, facade
composer require papi-ai/symfony     # Symfony bundle, DI, Messenger
```

Optional extras:

```bash
composer require papi-ai/effect      # Async video and fiber-based execution
composer require papi-ai/rtk         # Token-optimisation proxy (needs the rtk binary)
```

## Requirements

- PHP 8.2+
- `ext-curl` (for provider packages)
- Zero runtime dependencies in core
