# Flattened Vyper

## Description 

I achieve to obtain this smart contract, but I can't understand what it does. Can you help me?
  
The only information that I have are the followings:
- The flag is cut in three parts and each part is emitted once.
- The first part is emitted in raw bytes.
- The second part is emitted in base58 encoding.
- The last part has to be xor-ed with the second part.

## Author
- [Fabrisme](https://x.com/FabrismeGoeland) 

## Difficulty
- Medium

## Attachments

- [VyperVault.bin](attachments/VyperVault.bin)

## Usage

Lauch the challenge:
```sh
cd build 
docker compose up --build -d
```

## Writeups

- [Author's writeup](solve/README.md)
