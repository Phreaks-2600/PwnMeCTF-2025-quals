# Copyright 2013 Eviatar Bach, eviatarbach@gmail.com
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
Implementation of a Takuzu solver.

A Takuzu board consists of a square grid of binary cells. There must be an
equal number of 0s and 1s in every row and column, no duplicate rows or
columns, and no more than two of the same bit consecutive in every row and
column.
"""

from ortools.constraint_solver import pywrapcp  # type: ignore
from takuzu_utils import Board, BoardSolved, parse_cell


def valid(board: BoardSolved) -> bool:
    """
    Checks whether a board has no duplicate rows or columns. This is needed to
    filter out invalid solutions from the constraint solver.
    """
    return (len(set(map(tuple, board))) == len(board)) and (len(set(zip(*board))) == len(board))


def solve(board: Board) -> BoardSolved:
    """
    Solves a Takuzu board, with None for empty (unsolved) spaces
    """
    assert len(set(map(len, board))) == 1  # all row lengths are the same
    assert len(board) == len(board[0])  # width and height are the same
    assert len(board) % 2 == 0  # board has even dimensions

    line_sum = len(board) // 2  # the number to which all rows and columns sum
    line = range(len(board))  # line and row indices

    solver = pywrapcp.Solver("takuzu")

    grid: dict[tuple[int, int], pywrapcp.IntVar] = {}
    for i in line:
        for j in line:
            grid[(i, j)] = solver.IntVar(0, 1, "grid %i %i" % (i, j))

    # initial values
    for i in line:
        for j in line:
            if board[i][j] is not None:
                solver.Add(grid[(i, j)] == board[i][j])

    # no three consecutive elements in rows or columns
    for i in line:
        for j in range(len(board) - 2):
            solver.Add(solver.SumGreaterOrEqual([grid[(i, jl)] for jl in line[j : j + 3]], 1))
            solver.Add(solver.SumLessOrEqual([grid[(i, jl)] for jl in line[j : j + 3]], 2))
            solver.Add(solver.SumGreaterOrEqual([grid[(jl, i)] for jl in line[j : j + 3]], 1))
            solver.Add(solver.SumLessOrEqual([grid[(jl, i)] for jl in line[j : j + 3]], 2))

    # rows and columns sum to half the size
    for i in line:
        solver.Add(solver.SumEquality([grid[(i, j)] for j in line], line_sum))

    for j in line:
        solver.Add(solver.SumEquality([grid[(i, j)] for i in line], line_sum))

    # regroup all variables into a list
    all_vars = [grid[(i, j)] for i in line for j in line]

    # create search phases
    vars_phase = solver.Phase(all_vars, solver.INT_VAR_SIMPLE, solver.INT_VALUE_SIMPLE)

    # search for all solutions and remove those with duplicate rows or columns
    solver.NewSearch(vars_phase)

    solutions: list[BoardSolved] = []

    while solver.NextSolution():
        solutions.append([[parse_cell(grid[(i, j)].Value(), False) for j in line] for i in line])

    solver.EndSearch()

    solutions = [s for s in solutions if valid(s)]
    assert len(solutions) == 1, "Multiple solutions found"

    return solutions[0]
