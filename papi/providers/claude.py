import os
from anthropic import Anthropic
from papi.providers.base import LLMProvider

class ClaudeProvider(LLMProvider):
    def __init__(self, api_key: str = None):
        self.api_key = api_key or os.environ.get("ANTHROPIC_API_KEY")
        if not self.api_key:
            raise ValueError("Anthropic API key is required.")
        self.client = Anthropic(api_key=self.api_key)

    def complete(self, prompt: str, model: str = "claude-3-opus-20240229", max_tokens: int = 1024) -> str:
        """
        Generates a completion for the given prompt using Claude.
        """
        message = self.client.messages.create(
            max_tokens=max_tokens,
            messages=[
                {
                    "role": "user",
                    "content": prompt,
                }
            ],
            model=model,
        )
        return message.content[0].text
