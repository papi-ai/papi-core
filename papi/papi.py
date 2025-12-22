from typing import Dict, Type, Optional
from papi.providers.base import LLMProvider
from papi.providers.claude import ClaudeProvider
from papi.providers.openai import OpenAIProvider

class Papi:
    _providers: Dict[str, Type[LLMProvider]] = {
        "claude": ClaudeProvider,
        "openai": OpenAIProvider,
    }

    def __init__(self, provider_name: str = "claude", **kwargs):
        self.current_provider = self._get_provider(provider_name, **kwargs)

    def _get_provider(self, name: str, **kwargs) -> LLMProvider:
        if name not in self._providers:
            raise ValueError(f"Provider '{name}' not supported. Available: {list(self._providers.keys())}")
        return self._providers[name](**kwargs)

    def using(self, provider_name: str, **kwargs) -> 'Papi':
        """
        Switch to a different provider.
        """
        self.current_provider = self._get_provider(provider_name, **kwargs)
        return self

    def complete(self, prompt: str, **kwargs) -> str:
        """
        Generate a completion using the current provider.
        """
        return self.current_provider.complete(prompt, **kwargs)
