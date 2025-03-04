#!/usr/bin/env python3
from challenge import challenge
import json
import sys

def main():
    print("Welcome to my minecraft map hash")
    sys.stdout.flush()
    try:
        while True:
            input_json = sys.stdin.readline()
            if not input_json:
                break
            json_received = json.loads(input_json)
            result = challenge(json_received)
            output_json = json.dumps(result)
            print(output_json)
            sys.stdout.flush()
    except Exception as e:
        error_message: dict[str, str] = {"error": str(e)}
        print(json.dumps(error_message))


if __name__ == "__main__":
    main()
