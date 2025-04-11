import sys
import json
import os
from dotenv import load_dotenv
from huggingface_hub import InferenceClient

# Load environment variables from .env file
load_dotenv()

def main():
    # Read the prompt from the command-line arguments
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No prompt provided."}))
        return

    prompt = sys.argv[1]

    # Get API key from environment variable
    api_key = os.getenv('HUGGINGFACE_API_KEY')
    if not api_key:
        print(json.dumps({"error": "HUGGINGFACE_API_KEY not found in environment variables"}))
        return

    # Initialize the Hugging Face client
    client = InferenceClient(
        provider="nebius",
        api_key=api_key
    )

    # Prepare the messages for the API
    messages = [
        {
            "role": "user",
            "content": prompt
        }
    ]

    try:
        # Call the Hugging Face API
        response = client.chat.completions.create(
            model="aaditya/Llama3-OpenBioLLM-70B",
            messages=messages,
            temperature=0.5,
            max_tokens=2048,
            top_p=0.7,
            stream=False
        )

        # Extract the response content
        if isinstance(response, dict) and "choices" in response:
            result = response["choices"][0]["message"]["content"]
            # Combine debug_response and response in a single JSON object
            print(json.dumps({
                "debug_response": response,
                "response": result
            }))
        else:
            print(json.dumps({"error": "Unexpected response structure."}))

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()