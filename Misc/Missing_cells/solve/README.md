# Missing Cells - Writeup

- [Analysis](#analysis)
- [Solution](#solution)
  - [Step 1: Recovering the known OTP bits](#step-1-recovering-the-known-otp-bits)
  - [Step 2: Solving the grid](#step-2-solving-the-grid)
  - [Step 3: Decoding the flag](#step-3-decoding-the-flag)
- [Solve script](#solve-script)

## Analysis

The challenge mentions a binary sudoku grid. A quick search reveals that this is probably a variant of sudoku called Takuzu, or Binairo (cf. [Wikipedia](https://en.wikipedia.org/wiki/Takuzu)).

The rules are simple:

- The grid is a rectangle of even size, usually square.
- It is filled with 0s and 1s, with some cells missing.
- Each row and column must contain an equal number of 0s and 1s.
- No more than two identical numbers can be adjacent horizontally or vertically.
- Each row and column must be unique.
- A solvable grid must have a unique solution.

The grid provided in the challenge is a 16×16 Takuzu grid. The missing cells are represented by dots. We can assume that solving the grid will help decode the secret message.

In addition to the grid, the challenge provides a secret message in hexadecimal format, and a hint that the flag format is important.

The flag has 32 characters, and the secret message is 64 hex characters, or 32 bytes, which is the same length.

Paying closer attention to the grid, we can notice that a size of 16×16 gives 256 cells, which is exactly the number of bits in 32 bytes. We can hypothesize that the solved grid is a one-time pad for the secret message, given in binary. This is further supported by the wording of the challenge, which suggests that the solution will help "unmask" the secret (OTP is effectively a XOR mask).

The objective seems clear: solve the Takuzu grid, and XOR its binary representation with the secret message to reveal the flag.

However, trying some online solvers or scripts reveals that the grid is not solvable: it has multiple solutions (a lot, in fact, which makes bruteforcing impractical). Indeed, there seems to be very few filled cells (only 39 out of 256).

This would be a dead end, if not for the hint about the flag format. We know that the flag starts with `PWNME{` and ends with `}`. That gives us 7 characters of known plaintext, which, when XORed with the secret message, will reveal the value of the first 6 bytes and the last byte of the OTP. This translates to the first 48 and last 8 cells of the grid.

This seems to be confirmed by the grid itself: the first 3 rows (48 cells) and the second half of the last row (8 cells) are completely empty. This suggests that those were likely removed from the solvable grid to prevent having a single solution for the challenge.

## Solution

### Step 1: Recovering the known OTP bits

The first step is to recover the known bits of the OTP. To do this, we XOR the known parts of the flag with the corresponding parts of the secret message.

```python
def xor(a: bytes, b: bytes) -> bytes:
    return bytes(x ^ y for x, y in zip(a, b))

def bytes_to_bits(data: bytes) -> str:
    return "".join(f"{b:08b}" for b in data)

SECRET = bytes.fromhex("c56217e72f2ee27f0ec1f00f06956493ab02a2f8072159f3a27e79d399a4924f")
FLAG_START = b"PWNME{"
FLAG_END = b"}"
known_start = bytes_to_bits(xor(SECRET[:len(FLAG_START)], FLAG_START))
known_end = bytes_to_bits(xor(SECRET[-len(FLAG_END):], FLAG_END))
print("Known start:", known_start)
print("Known end:  ", known_end)
```

We get the following output:

```text
Known start: 100101010011010101011001101010100110101001010101
Known end:   00110010
```

We can now fill in the first 48 and last 8 cells of the grid with these bits:

```text
1 0 0 1 0 1 0 1 0 0 1 1 0 1 0 1
0 1 0 1 1 0 0 1 1 0 1 0 1 0 1 0
0 1 1 0 1 0 1 0 0 1 0 1 0 1 0 1
. . . . . . . . 0 . . . . . 1 1
. 1 1 . . 1 . 1 . . . 1 . . . .
1 . . 0 . . . . . . . 1 . . . .
. . . . . . . 1 . 0 . . 0 . 0 .
0 . . . . . . . . . . . . . 0 0
. . 0 0 . . . . . . 1 1 . . . .
. . . . . . 0 . 1 . . . . . . 0
. . 1 . . 1 . . . 1 . . . . 1 .
. 1 1 . . . . . . . . 0 . . . .
. . . . . . 1 . . . . . 1 . 1 1
. . . . . . . . . . . . . . . .
. . . . . . 0 . . 1 . . . . . 0
. . . . . . . . 0 0 1 1 0 0 1 0
```

### Step 2: Solving the grid

Now let's solve the grid! There are many ways to do this, for example using an [online solver](https://binarypuzzle.nl/) or a Python script. I used the script found on [this page](https://code.activestate.com/recipes/578414-takuzu-solver/), which I adapted to work with Python 3. It uses a Python wrapper for the [constraint solver](https://developers.google.com/optimization/reference/constraint_solver/constraint_solver) from Google's Operations Research tools. The modified script is available in [`takuzu_solver.py`](./takuzu_solver.py).

It is extremely fast and provides the solution in a fraction of a second.

The solved grid is as follows:

```text
1 0 0 1 0 1 0 1 0 0 1 1 0 1 0 1
0 1 0 1 1 0 0 1 1 0 1 0 1 0 1 0
0 1 1 0 1 0 1 0 0 1 0 1 0 1 0 1
1 0 0 1 0 1 1 0 0 1 0 0 1 0 1 1
0 1 1 0 0 1 0 1 1 0 1 1 0 1 0 0
1 0 1 0 1 0 1 0 0 1 0 1 1 0 1 0
0 1 0 1 1 0 0 1 1 0 1 0 0 1 0 1
0 0 1 1 0 1 1 0 1 1 0 0 1 1 0 0
1 1 0 0 1 0 0 1 0 0 1 1 0 0 1 1
1 1 0 0 1 1 0 0 1 1 0 0 1 1 0 0
0 0 1 1 0 1 1 0 0 1 0 1 0 0 1 1
0 1 1 0 1 0 0 1 1 0 1 0 1 1 0 0
1 0 0 1 0 0 1 1 0 1 0 0 1 0 1 1
0 0 1 0 0 1 1 0 1 0 1 1 0 1 0 1
1 1 0 0 1 1 0 0 1 1 0 0 1 0 1 0
1 0 1 1 0 0 1 1 0 0 1 1 0 0 1 0
```

### Step 3: Decoding the flag

Finally, we can XOR the solved grid with the secret message to reveal the flag:

```python
otp = int(
    "1001010100110101010110011010101001101010010101011001011001001011"
    "0110010110110100101010100101101001011001101001010011011011001100"
    "1100100100110011110011001100110000110110010100110110100110101100"
    "1001001101001011001001101011010111001100110010101011001100110010",
    2).to_bytes(32, "big")
flag = xor(SECRET, otp).decode()
print("Flag:", flag)
```

And we get the flag!

```text
Flag: PWNME{t4kuZU_0R_b1n41r0_15_fUn!}
```

## Solve script

A fully automated script is available in [`solve.py`](./solve.py). It uses an overly complicated [`TakuzuBoard`](./takuzu_utils.py#L15) class to encapsulate the grid because I wanted to have fun with OOP in Python.

To use it, simply run:

```sh
pip install -r requirements.txt # You may need to add --break-system-packages for some systems
python solve.py
```
