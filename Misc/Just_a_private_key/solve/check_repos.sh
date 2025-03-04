#!/bin/bash

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <username> <wordlist>"
    exit 1
fi

USERNAME=$1
WORDLIST=$2

if [ ! -f "$WORDLIST" ]; then
    echo "The file $WORDLIST does not exist."
    exit 1
fi

echo "Bruteforcing the repositories..."
while IFS= read -r LINE; do
    OUTPUT=$(git ls-remote git@github.com:"$USERNAME"/"$LINE".git 2>&1)
    if [[ ! $OUTPUT == *"ERROR: Repository not found"* ]]; then
        echo "The repo $LINE exists"
	echo "Clone the repo : git clone git@github.com:$USERNAME/$LINE.git"
    fi
done < "$WORDLIST"
