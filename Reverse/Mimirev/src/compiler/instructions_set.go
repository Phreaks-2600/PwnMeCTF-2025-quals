package compiler

import (
	"fmt"
	"log"
)

const (
	PUSH1 = iota
	PUSH2
	PUSH3
	PUSH4

	SLOAD
	SSTORE

	ADD
	SUB
	DIVIDE
	MULTIPLY
	MODOP

	JUMP
	JUMPI

	EQOP
	NEQOP
	GTOP
	GTEQOP
	LTOP
	LTEQOP
	JUMPDEST
	VERIFYPROOFOP
	PRINTOP
)

var instructions_str_table map[int]string = map[int]string{
	PUSH1:         "PUSH1",
	PUSH2:         "PUSH2",
	PUSH3:         "PUSH3",
	PUSH4:         "PUSH4",
	SLOAD:         "SLOAD",
	SSTORE:        "SSTORE",
	ADD:           "ADD",
	SUB:           "SUB",
	DIVIDE:        "DIVIDE",
	MULTIPLY:      "MULTIPLY",
	JUMP:          "JUMP",
	JUMPI:         "JUMPI",
	EQOP:          "EQ",
	NEQOP:         "NEQ",
	GTOP:          "GT",
	GTEQOP:        "GTEQ",
	LTOP:          "LT",
	LTEQOP:        "LTEQ",
	JUMPDEST:      "JUMPDEST",
	VERIFYPROOFOP: "VERIFYPROOF",
	MODOP:         "MOD",
	PRINTOP:       "PRINT",
}

func Disassembler(bytecodes []byte) {
	fmt.Println(bytecodes)
	length := len(bytecodes)
	i := 0
	for i < length {
		instr := int(bytecodes[i])
		instr_str, ok := instructions_str_table[instr]
		if !ok {
			log.Fatalf("Invalid instruction at 0x%x: %v", i, instr)
		}

		var disassembled_instruction_string string
		switch instr {
		case ADD, SUB, DIVIDE, MULTIPLY, JUMP, JUMPDEST, JUMPI, EQOP, NEQOP, GTOP, GTEQOP, LTOP, LTEQOP, VERIFYPROOFOP, MODOP, PRINT:
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v", i, instr_str)
			i++
		case PUSH1:
			if i+1 >= length {
				log.Fatalf("Truncated PUSH1 at 0x%x", i)
			}
			op := instr_str
			source := bytecodes[i+1]
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v 0x%x", i, op, source)
			i += 2
		case PUSH2:
			if i+2 >= length {
				log.Fatalf("Truncated PUSH2 at 0x%x", i)
			}
			op := instr_str
			source := bytecodes[i+1 : i+3]
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v 0x%x", i, op, BytesToIntBigEndian(source))
			i += 3
		case PUSH3:
			if i+3 >= length {
				log.Fatalf("Truncated PUSH3 at 0x%x", i)
			}
			op := instr_str
			source := bytecodes[i+1 : i+4]
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v 0x%x", i, op, BytesToIntBigEndian(source))
			i += 4
		case PUSH4:
			if i+4 >= length {
				log.Fatalf("Truncated PUSH4 at 0x%x", i)
			}
			op := instr_str
			source := bytecodes[i+1 : i+5]
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v 0x%x", i, op, BytesToIntBigEndian(source))
			i += 5
		case SSTORE, SLOAD:
			if i+1 >= length {
				log.Fatalf("Truncated %v at 0x%x", instr_str, i)
			}
			op := instr_str
			source := bytecodes[i+1]
			disassembled_instruction_string = fmt.Sprintf("[0x%x] : %v 0x%x", i, op, source)
			i += 2
		default:
			log.Fatalf("Unhandled instruction at 0x%x: %v", i, instr)
		}
		fmt.Println(disassembled_instruction_string)
	}
}
