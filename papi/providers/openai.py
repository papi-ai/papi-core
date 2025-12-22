import os
import openai
from papi.providers.base import LLMProvider

class OpenAIProvider(LLMProvider):
    def __init__(self, api_key: str = None):
        self.api_key = api_key or os.environ.get("OPENAI_API_KEY")
        if not self.api_key:
            raise ValueError("OpenAI API key is required.")
        self.client = openai.OpenAI(api_key=self.api_key)

    def complete(self, prompt: str, model: str = "gpt-4o", max_tokens: int = 1024) -> str:
        """
        Generates a completion for the given prompt using OpenAI.
        """
        response = self.client.chat.completions.create(
            model=model,
            messages=[
                {"role": "user", "content": prompt}
            ],
            max_tokens=max_tokens
        )
        return response.choices[0].message.content
