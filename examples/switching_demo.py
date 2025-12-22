import os
import sys
from dotenv import load_dotenv

# Ensure the papi package is in the python path
sys.path.append(os.path.join(os.path.dirname(__file__), '..'))

from papi import Papi

def main():
    load_dotenv()
    
    print("Initializing Papi (defaulting to Claude)...")
    try:
        # Initialize
        papi = Papi()
        
        # Test Claude (if key exists)
        if os.getenv("ANTHROPIC_API_KEY"):
            print("\n--- Testing Claude ---")
            response = papi.complete("Say hello from Claude!")
            print(f"Claude Response: {response}")
        else:
            print("\nSkipping Claude test (no ANTHROPIC_API_KEY found)")

        # Switch to OpenAI
        print("\nSwitching to OpenAI...")
        if os.getenv("OPENAI_API_KEY"):
            papi.using("openai")
            print("\n--- Testing OpenAI ---")
            response = papi.complete("Say hello from OpenAI!")
            print(f"OpenAI Response: {response}")
        else:
            print("\nSkipping OpenAI test (no OPENAI_API_KEY found)")
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
