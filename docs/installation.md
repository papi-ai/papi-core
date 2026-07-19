# Installation

Install the core package and the provider(s) you need:

```bash
composer require papi-ai/core

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

## Requirements

- PHP 8.2+
- `ext-curl` (for provider packages)
- Zero runtime dependencies in core
