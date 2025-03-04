from enum import Enum
from functools import cached_property
from itertools import islice
from math import isqrt
from typing import Generator, Iterable, Literal, Self, Sequence, TypeAlias, TypeVar, overload

Cell: TypeAlias = Literal[0, 1] | None
CellSolved: TypeAlias = Literal[0, 1]
Board: TypeAlias = Sequence[Sequence[Cell]]
BoardSolved: TypeAlias = Sequence[Sequence[CellSolved]]

_T = TypeVar("_T")


class TakuzuBoard(str):
    def __new__(cls, board: str) -> Self:
        board = board.replace("\n", "").replace(" ", "")
        assert board, "Board is empty"
        assert all(c in "01." for c in board), "Invalid characters (all must be 0, 1, or .)"
        length = len(board)
        size = isqrt(length)
        assert size**2 == length, f"Board is not square ({length = })"
        assert size % 2 == 0, f"Board size is not even ({size = })"
        return super().__new__(cls, board)

    def __repr__(self) -> str:
        return f"{self.__class__.__name__}({str(self)!r})"

    def __int__(self) -> int:
        return int(self, 2)

    @cached_property
    def size(self) -> int:
        return isqrt(len(self))

    def is_solved(self, do_raise: bool = False) -> bool:
        if "." in self:
            if do_raise:
                raise ValueError("Board is not complete")
            return False

        size = self.size
        rows: list[str] = []
        cols: list[str] = []

        for i in range(size):
            row = self[i * size : (i + 1) * size]
            col = self[i::size]
            if row in rows or col in cols:
                if do_raise:
                    raise ValueError("Board is invalid: duplicate rows/columns")
                return False
            if row.count("0") != row.count("1") or col.count("0") != col.count("1"):
                if do_raise:
                    raise ValueError("Board is invalid: different number of 0 and 1 in row/column")
                return False
            if "000" in row or "111" in row or "000" in col or "111" in col:
                if do_raise:
                    raise ValueError("Board is invalid: more than two adjacent 0/1 in row/column")
                return False
            rows.append(row)
            cols.append(col)

        return True

    def pretty(self) -> str:
        length = len(self)
        size = self.size
        assert size**2 == length, f"Board is not square ({length = })"
        return "\n".join(" ".join(row) for row in batched(self, size))

    def replace_at(self, index: int, value: str) -> Self:
        if index < 0:
            index += len(self) - len(value) + 1
        if index < 0 or index + len(value) > len(self):
            raise IndexError(f"board index out of range: {index}")
        return self.__class__(self[:index] + value + self[index + len(value) :])

    def to_matrix(self) -> Board:
        size = self.size
        return [[parse_cell(self[i * size + j]) for j in range(size)] for i in range(size)]

    @classmethod
    def from_matrix(cls, matrix: Board) -> Self:
        return cls("".join("".join(map(str, row)) for row in matrix))


class CellType(Enum):
    ZERO = {0, "0"}
    ONE = {1, "1"}
    EMPTY = {None, "."}

    @classmethod
    def is_zero(cls, cell: str | int | None) -> bool:
        return cell in cls.ZERO.value

    @classmethod
    def is_one(cls, cell: str | int | None) -> bool:
        return cell in cls.ONE.value

    @classmethod
    def is_empty(cls, cell: str | int | None) -> bool:
        return cell in cls.EMPTY.value


def xor(a: bytes, b: bytes) -> bytes:
    return bytes(x ^ y for x, y in zip(a, b))


def bytes_to_bits(data: bytes) -> str:
    return "".join(f"{b:08b}" for b in data)


def batched(iterable: Iterable[_T], n: int) -> Generator[tuple[_T, ...], None, None]:
    if n < 1:
        raise ValueError("n must be at least one")
    it = iter(iterable)
    while batch := tuple(islice(it, n)):
        yield batch


@overload
def parse_cell(cell: str | int | None, allow_empty: Literal[True] = ...) -> Cell: ...
@overload
def parse_cell(cell: str | int | None, allow_empty: Literal[False]) -> CellSolved: ...


def parse_cell(cell: str | int | None, allow_empty: bool = True) -> Cell | CellSolved:
    if allow_empty and CellType.is_empty(cell):
        return None
    if CellType.is_zero(cell):
        return 0
    if CellType.is_one(cell):
        return 1
    raise ValueError(f"Invalid cell: {cell}")
