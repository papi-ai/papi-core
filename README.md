# Papi Core

A Python library for interacting with LLM providers, starting with Claude.

## Setup

1. Clone the repository.
2. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
3. Set up your environment variables. Create a `.env` file in the root directory:
   ```
   ANTHROPIC_API_KEY=your_api_key_here
   ```

## Usage

### Basic Completion with Claude

You can use the `ClaudeProvider` to generate text completions.

```python
import os
from dotenv import load_dotenv
from papi.providers.claude import ClaudeProvider

# Load environment variables
load_dotenv()

api_key = os.getenv("ANTHROPIC_API_KEY")

# Initialize provider
provider = ClaudeProvider(api_key=api_key)

# Generate completion
response = provider.complete(
    prompt="Hello, Claude!",
    model="claude-3-opus-20240229",
    max_tokens=100
)

print(response)
```

### Running the Demo

To run the provided example script:

```bash
python examples/demo.py
```
