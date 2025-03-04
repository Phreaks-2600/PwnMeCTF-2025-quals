#!/usr/bin/env python3

from Crypto.Util.number import long_to_bytes
from takuzu_solver import solve
from takuzu_utils import TakuzuBoard, bytes_to_bits, xor

SECRET = bytes.fromhex("c56217e72f2ee27f0ec1f00f06956493ab02a2f8072159f3a27e79d399a4924f")
BOARD = """
. . . . . . . . . . . . . . . .
. . . . . . . . . . . . . . . .
. . . . . . . . . . . . . . . .
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
. . . . . . . . . . . . . . . .
"""

FLAG = b"PWNME{.}"


def main() -> None:
    board = TakuzuBoard(BOARD)
    print("Initial board:")
    print(board.pretty())
    print()

    flag_parts = [part for part in FLAG.split(b".") if part]
    assert len(flag_parts) == 2
    flag_start, flag_end = flag_parts
    print("Flag start:", flag_start)
    print("Flag end:  ", flag_end)
    flag_start_len, flag_end_len = map(len, flag_parts)

    known_start = bytes_to_bits(xor(SECRET[:flag_start_len], flag_start))
    known_end = bytes_to_bits(xor(SECRET[-flag_end_len:], flag_end))
    print("Known start:", known_start)
    print("Known end:  ", known_end)
    print()

    board_rec = board.replace_at(0, known_start).replace_at(-1, known_end)
    print("Reconstructed board:")
    print(board_rec.pretty())
    print()

    solution = solve(board_rec.to_matrix())
    board_solved = TakuzuBoard.from_matrix(solution)
    print("Solution:")
    print(board_solved.pretty())
    print()
    assert board_solved.is_solved(do_raise=True), "Invalid board"
    assert len(board_solved) == len(board), f"Invalid board length (expected {len(board)})"

    solved_int = int(board_solved)
    solved_bytes = long_to_bytes(solved_int, len(board_solved) // 8)
    flag = xor(solved_bytes, SECRET)
    print("Flag:", flag.decode())


if __name__ == "__main__":
    main()
