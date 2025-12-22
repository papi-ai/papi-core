import os
import sys

# Ensure the papi package is in the python path
sys.path.append(os.path.join(os.path.dirname(__file__), '..'))

from papi.providers.claude import ClaudeProvider

def main():
    # Check for API Key
    if not os.environ.get("ANTHROPIC_API_KEY"):
        print("Please set the ANTHROPIC_API_KEY environment variable.")
        return

    try:
        provider = ClaudeProvider()
        prompt = "Hello Claude, can you write a haiku about coding?"
        print(f"Prompt: {prompt}\n")
        
        response = provider.complete(prompt)
        print(f"Response:\n{response}")
    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    main()
