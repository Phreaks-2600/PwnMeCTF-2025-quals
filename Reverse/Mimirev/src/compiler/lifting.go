package compiler

import (
	"fmt"
)

type Lifter struct {
	Parser            *Parser
	Storage           map[string]StorageElement
	StorageCounter    int
	CompiledProgram   []byte
	Labels            map[string]int
	PendingLabels     map[int]string
	CurrentLoopOffset int
}

type StorageElement struct {
	VariableName string
	Index        int
}

func NewLifter(source string) *Lifter {
	return &Lifter{
		Parser:          NewParser(source),
		Storage:         make(map[string]StorageElement),
		StorageCounter:  0,
		CompiledProgram: make([]byte, 0),
		Labels:          make(map[string]int),
		PendingLabels:   make(map[int]string),
	}
}

func (lift *Lifter) Compile() ([]byte, error) {
	ast, err := lift.Parser.Parse()
	if err != nil {
		return nil, err
	}
	lift.compile(ast)

	return lift.CompiledProgram, nil
}

func (lift *Lifter) compile(ast Program) int {
	return lift.compileBlock(ast.Block.Statements)
}

func (lift *Lifter) compileBlock(statements []Node) int {
	posBeforeBlock := len(lift.CompiledProgram) - 1
	for _, statement := range statements {
		switch state := statement.(type) {
		case InitialiseStatement:
			lift.compileInitialiseStatement(state)

		case ReassignmentStatement:
			lift.compileReaStatement(state)

		case IfStatement:
			lift.compileIfStatement(state)
		case WhileStatement:
			lift.compileWhileStatement(state)
		case VerifyProofStatement:
			lift.compileVerifyProofStatement(state)
		case PrintStatement:
			lift.compilePrintStatement(state)
			/*case BreakStatement:
			lift.compileBreakStatement(state)*/

		}
	}
	return posBeforeBlock
}

func (lift *Lifter) compilePrintStatement(printStatement PrintStatement) int {
	posBeforePrintStatement := len(lift.CompiledProgram) - 1
	lift.compileExpression(printStatement.Expression)
	lift.CompiledProgram = append(lift.CompiledProgram, PRINTOP)
	return posBeforePrintStatement
}

func (lift *Lifter) compileVerifyProofStatement(verifyProofStatement VerifyProofStatement) int {
	posBeforeVerifyProofStatement := len(lift.CompiledProgram) - 1

	if len(verifyProofStatement.Arguments) > 2 {
		panic(fmt.Sprintf("The verifyProof statement only accepts 2 arguments."))
	}

	for _, argument := range verifyProofStatement.Arguments {
		lift.compileExpression(argument)
	}
	lift.CompiledProgram = append(lift.CompiledProgram, VERIFYPROOFOP)
	return posBeforeVerifyProofStatement
}

/*
func (lift *Lifter) compileBreakStatement(breakStatement BreakStatement) int {
	posBeforeBreakStatement := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
	return posBeforeBreakStatement
}*/

func (lift *Lifter) compileWhileStatement(whileStatement WhileStatement) int {
	posBeforeWhileStatement := len(lift.CompiledProgram) - 1
	posBeforeCondition := lift.compileCondition(whileStatement.Condition)
	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posPush1JumpiTrue := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMPI)

	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posPush1JumpFalse := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
	posStartBodyWhile := lift.compileBlock(whileStatement.Body.Statements)
	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posEndBodyPushWhile := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
	lift.CompiledProgram = append(lift.CompiledProgram, JUMPDEST)
	posEndWhile := len(lift.CompiledProgram) - 1
	copy(lift.CompiledProgram[posEndBodyPushWhile-3:posEndBodyPushWhile+1], IntToBytesBigEndianTwo(posBeforeCondition+1))
	copy(lift.CompiledProgram[posPush1JumpiTrue-3:posPush1JumpiTrue+1], IntToBytesBigEndianTwo(posStartBodyWhile+1))
	copy(lift.CompiledProgram[posPush1JumpFalse-3:posPush1JumpFalse+1], IntToBytesBigEndianTwo(posEndWhile))
	/*
		PUSH1 0x0
		SSTORE 0x0

		SLOAD 0x0
		PUSH 0xA
		LT
		PUSH4 0x0 <- JUMP to the trueBranch so the body of loop
		JUMPI
		PUSH4 0x0
		SLOAD 0x0
		PUSH 0x1
		ADD
		SSTORE 0x0
		PUSH4 0x0 -> JUMP to the condition to recheck condition
		JUMP
	*/

	return posBeforeWhileStatement
}

func (lift *Lifter) compileInitialiseStatement(initStatement InitialiseStatement) int {
	posAfterInitialiseStatement := len(lift.CompiledProgram) - 1
	variableName := initStatement.Identifier.(Identifier).Ident
	lift.compileExpression(initStatement.Expression)

	if _, ok := lift.Storage[variableName]; ok {
		panic(fmt.Sprintf("The variable %v are already initialised. Tips: use 'rea' to reassign %v.", variableName, variableName))
	}

	lift.Storage[variableName] = StorageElement{
		VariableName: variableName,
		Index:        lift.StorageCounter,
	}

	lift.StorageCounter++

	lift.CompiledProgram = append(lift.CompiledProgram, SSTORE, byte(lift.Storage[variableName].Index))
	return posAfterInitialiseStatement
}

func (lift *Lifter) compileReaStatement(reaStatement ReassignmentStatement) int {
	posAfterReaStatement := len(lift.CompiledProgram) - 1

	variableName := reaStatement.Identifier.(Identifier).Ident

	if _, ok := lift.Storage[variableName]; !ok {
		panic(fmt.Sprintf("The variable %v does not exist. TIPS: use 'init' to initialize the variable %v", variableName, variableName))
	}

	lift.compileExpression(reaStatement.Expression)

	lift.CompiledProgram = append(lift.CompiledProgram, SSTORE, byte(lift.Storage[variableName].Index))
	return posAfterReaStatement
}

func (lift *Lifter) getCurrentInstructions() []byte {
	return lift.CompiledProgram
}

func (lift *Lifter) compileIfStatement(ifStatement IfStatement) int {

	posBeforeIfStatement := len(lift.CompiledProgram) - 1
	lift.compileCondition(ifStatement.Condition)

	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posPush1JumpiTrue := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMPI)

	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posPush1JumpFalse := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
	postStartIf := len(lift.CompiledProgram) - 1
	lift.compileBlock(ifStatement.Consequent.Statements)
	lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
	posEndIfTrue := len(lift.CompiledProgram) - 1
	lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
	copy(lift.CompiledProgram[posPush1JumpiTrue-3:posPush1JumpiTrue+1], IntToBytesBigEndianTwo(postStartIf+1))
	posEndIf := len(lift.CompiledProgram) - 1

	if len(ifStatement.Alternate.Statements) > 0 {
		posStartElse := len(lift.CompiledProgram) - 1
		copy(lift.CompiledProgram[posPush1JumpFalse-3:posPush1JumpFalse+1], IntToBytesBigEndianTwo(posStartElse+1))
		lift.compileBlock(ifStatement.Alternate.Statements)

		lift.CompiledProgram = append(lift.CompiledProgram, PUSH4, 0x0, 0x0, 0x0, 0x0)
		posEndPushFalse := len(lift.CompiledProgram) - 1
		lift.CompiledProgram = append(lift.CompiledProgram, JUMP)
		posEndElse := len(lift.CompiledProgram) - 1
		copy(lift.CompiledProgram[posEndPushFalse-3:posEndPushFalse+1], IntToBytesBigEndianTwo(posEndElse+1))
		copy(lift.CompiledProgram[posEndIfTrue-3:posEndIfTrue+1], IntToBytesBigEndianTwo(posEndElse+1))
		lift.CompiledProgram = insertByteAt(lift.CompiledProgram, posEndElse+1, JUMPDEST)

	} else {
		copy(lift.CompiledProgram[posPush1JumpFalse-3:posPush1JumpFalse+3], IntToBytesBigEndianTwo(posEndIf+1))
		lift.CompiledProgram = insertByteAt(lift.CompiledProgram, posEndIf+1, JUMPDEST)
		copy(lift.CompiledProgram[posEndIfTrue-3:posEndIfTrue+3], IntToBytesBigEndianTwo(posEndIf+1))

	}

	return posBeforeIfStatement
}

func (lift *Lifter) compileCondition(condition Node) int {
	posBeforeCondition := len(lift.CompiledProgram) - 1
	switch expr := condition.(type) {
	case BinOP:
		lift.compileExpression(expr.Lhs)
		lift.compileExpression(expr.Rhs)

		switch expr.Op {
		case "==":
			lift.CompiledProgram = append(lift.CompiledProgram, EQOP)
		case "!=":
			lift.CompiledProgram = append(lift.CompiledProgram, NEQOP)
		case "<":
			lift.CompiledProgram = append(lift.CompiledProgram, LTOP)
		case ">":
			lift.CompiledProgram = append(lift.CompiledProgram, GTOP)
		case ">=":
			lift.CompiledProgram = append(lift.CompiledProgram, GTEQOP)
		case "<=":
			lift.CompiledProgram = append(lift.CompiledProgram, LTEQOP)
		}
	case NumberLiteral:
		lift.compileExpression(expr)
	case Identifier:
		lift.compileExpression(expr)
	}

	return posBeforeCondition
}

func (lift *Lifter) compileExpression(expression Node) int {
	postBeforeCompileExpression := len(lift.CompiledProgram) - 1

	switch expr := expression.(type) {
	case NumberLiteral:
		number := expr.Number
		numBytesOfNumber := NBBytesFromInt(number)
		switch numBytesOfNumber {
		case 1:
			lift.CompiledProgram = append(lift.CompiledProgram, PUSH1)
		case 2:
			lift.CompiledProgram = append(lift.CompiledProgram, PUSH2)
		case 3:
			lift.CompiledProgram = append(lift.CompiledProgram, PUSH3)
		case 4:
			lift.CompiledProgram = append(lift.CompiledProgram, PUSH4)
		default:
			panic(fmt.Sprintf("MIMI are 32 bits virtual machine."))
		}

		numBytes := IntToBytesBigEndian(number)
		lift.CompiledProgram = append(lift.CompiledProgram, numBytes...)
	case BinOP:
		lift.compileExpression(expr.Lhs)
		lift.compileExpression(expr.Rhs)

		switch expr.Op {
		case "+":
			lift.CompiledProgram = append(lift.CompiledProgram, ADD)
		case "-":
			lift.CompiledProgram = append(lift.CompiledProgram, SUB)
		case "/":
			lift.CompiledProgram = append(lift.CompiledProgram, DIVIDE)
		case "*":
			lift.CompiledProgram = append(lift.CompiledProgram, MULTIPLY)
		case "%":
			lift.CompiledProgram = append(lift.CompiledProgram, MODOP)
		}
	case Identifier:
		variableName := expr.Ident

		if _, ok := lift.Storage[variableName]; !ok {
			panic(fmt.Sprintf("Undefined variable %v in Storage.", variableName))
		}

		lift.CompiledProgram = append(lift.CompiledProgram, SLOAD, byte(lift.Storage[variableName].Index))
	}
	return postBeforeCompileExpression
}
