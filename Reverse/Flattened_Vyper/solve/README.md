# Flattened Vyper writeup

## Challenge Description
We are given a smart contract's bytecode that emits a flag in three parts. However, the bytecode is obfuscated using a custom flattening technique. The information provided about the emitted flag is as follows:

- The first part is emitted as raw bytes.
- The second part is base58 encoded.
- The third part is XOR-ed with the second part.

The goal is to reconstruct and extract the flag using reverse engineering techniques.

---

## Hands on

### Decompile the bytecode
The first thing to do is to decompile the bytecode using a service like [Dedaub](https://app.dedaub.com/decompile).

In this case the decompiler feature doesn't work because the contract was compiled with a custom obfuscator but the disassembler work well.

By looking at the instruction we can see that only three `LOG1` opcodes are presents so we only need to reverse these parts.

The obfuscation technique used in this bytecode are the followings:
- CFG flattening
- Usage of transient storage to store the next jump location
- Basic complexification of values pushed to the stack using `ADD`, `SUB` and `XOR`

## Solution Approach

### Step 1: Disassembling the bytecode
The bytecode of the smart contract is disassembled using the `pyevmasm` library. The script processes the instructions block-by-block, identifying when a `LOG1` operation occurs. This operation is typically used in Ethereum smart contracts to emit logs, which in this case, are crucial to obtaining the flag.

```python
for inst in disassemble_all(unhexlify(BYTECODE)):
    if str(inst) == "INVALID":
        if have_log:
            have_log = False
            log_blocks.append(cur_block)
        cur_block = []
    elif str(inst) == "LOG1":
        have_log = True
    cur_block.append(inst)
```

This loop processes the bytecode to extract blocks of instructions containing `LOG1`, which we suspect holds the flag parts.

---

### Step 2: Extracting values from logs block
Each `LOG1` instruction is followed by values pushed onto the stack. The `extract_values` function interprets these values based on the operations executed in the contract:

```python
def extract_values(log_block):
    MAX_256BIT = 2**256 - 1
    stack = []

    for inst in log_block:
        if str(inst).startswith("PUSH"):
            stack.append(inst.operand)
        elif str(inst) == "XOR":
            result = (stack.pop() ^ stack.pop()) & MAX_256BIT
            stack.append(result)
        elif str(inst) == "ADD":
            result = (stack.pop() + stack.pop()) & MAX_256BIT
            stack.append(result)
        elif str(inst) == "SUB":
            result = ((stack.pop() - stack.pop()) % (MAX_256BIT + 1)) & MAX_256BIT
            stack.append(result)
        elif str(inst) == "LOG1":
            stack.pop()
            stack.pop()
            stack.pop()
            return hex(stack.pop())
```

This function processes each log block and extracts the emitted values by simulating stack operations.

---

### Step 3: Decoding the flag
Once the values are extracted from logs, we reconstruct the flag by handling the different encoding formats:

1. The first part is directly decoded as raw bytes.
2. The second part is base58-decoded.
3. The third part is XOR-ed with the second part to reveal the final segment.

```python
logs = [bytes.fromhex(extract_values(log_block)[2:].rstrip('0')) for log_block in log_blocks]
print(f"{logs[0].decode()}{base58.b58decode(logs[1].decode()).decode()}{bytes([a ^ b for a, b in zip(logs[2], logs[1])]).decode()}")
```

The final flag is reconstructed by concatenating all three parts.

---

## Conclusion
This script efficiently deobfuscates the bytecode and extracts the flag by:
- Identifying `LOG1` operations that emit parts of the flag.
- Simulating stack operations to recover emitted values.
- Decoding and reconstructing the flag from different encoding formats.

### Solving script
- [solve.py](solve.py)