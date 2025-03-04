package compiler

import (
	"fmt"
	"strconv"
)

type Parser struct {
	Lexer        *Lexer
	Tokens       Tokens
	CurrentToken Token
	PeekToken    Token
	CursorToken  int
}

func NewParser(source string) *Parser {
	return &Parser{
		Lexer:        NewLexer(source),
		Tokens:       Tokens{},
		CurrentToken: Token{},
		CursorToken:  0,
	}
}

func (parser *Parser) Match(kind int) bool {

	if parser.CurrentToken.Kind == EOF {
		return true
	}

	if parser.CurrentToken.Kind == kind {
		parser.PeekToken = parser.CurrentToken
		parser.CursorToken++
		parser.CurrentToken = parser.Tokens[parser.CursorToken]
		return true
	}

	return false
}

func (parser *Parser) Expect(kind int) {
	if !parser.Match(kind) {
		panic(fmt.Sprintf("ERROR: Expected: %v, instead: %v", tokens_str_table[kind], parser.CurrentToken.ToString()))
	}
}

func (parser *Parser) Parse() (Program, error) {
	tokens, err := parser.Lexer.Tokenize()
	if err != nil {
		return Program{}, err
	}

	parser.Tokens = tokens
	parser.CurrentToken = parser.Tokens[0]
	return parser.parse()
}

func (parser *Parser) parse() (Program, error) {

	program := Program{}
	program.Block = BlockStatement{}
	program.Block.Statements = append(program.Block.Statements, parser.GetStatement())
	for !parser.Match(EOF) {
		program.Block.Statements = append(program.Block.Statements, parser.GetStatement())
	}
	return program, nil
}

func (parser *Parser) GetStatement() Node {

	var node Node
	switch parser.CurrentToken.Kind {
	case INIT:
		node = parser.InitStatement()
	case REA:
		node = parser.ReaStatement()
	case IF:
		node = parser.IfStatement()
	case WHILE:
		node = parser.WhileStatement()
	case BREAK:
		node = parser.BreakStatement()
	case VERIFYPROOF:
		node = parser.VerifyProofStatement()
	case PRINT:
		node = parser.PrintStatement()
	case EOF:
	case NEWLINE:
		parser.Match(NEWLINE)
	default:
		panic("unknow statement")
	}
	return node
}

func (parser *Parser) PrintStatement() Node {
	parser.Expect(PRINT)
	parser.Expect(LPAREN)
	expression := parser.ExpressionStatement()
	parser.Expect(RPAREN)
	parser.Expect(SEMICOLON)
	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}
	return NewPrintStatement(expression)
}

func (parser *Parser) VerifyProofStatement() Node {
	parser.Expect(VERIFYPROOF)
	parser.Expect(LPAREN)
	arguments := parser.argumentsList()
	fmt.Println(arguments)
	parser.Expect(SEMICOLON)
	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}
	return NewVerifyProofStatement(arguments)
}

func (parser *Parser) argumentsList() []Node {
	arguments := []Node{}
	for !parser.Match(RPAREN) {
		arguments = append(arguments, parser.ExpressionStatement())
		if !parser.Match(COMMA) {
			parser.Expect(RPAREN)
			break
		}
	}
	return arguments
}
func (parser *Parser) BreakStatement() Node {
	parser.Expect(BREAK)
	parser.Expect(SEMICOLON)
	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}
	return NewBreakStatement()
}

func (parser *Parser) WhileStatement() Node {
	parser.Expect(WHILE)
	condition := parser.ConditionStatement()

	parser.Expect(LBRACK)
	body := parser.GetBlockStatements()

	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}

	return NewWhileStatement(condition, body)
}

func (parser *Parser) IfStatement() Node {
	parser.Expect(IF)
	condition := parser.ConditionStatement()

	parser.Expect(LBRACK)
	body := parser.GetBlockStatements()
	var alternate BlockStatement
	if parser.Match(ELSE) {
		alternate = parser.ElseStatement()
	}

	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}

	return NewIfStatement(condition, body, alternate)

}

func (parser *Parser) ElseStatement() BlockStatement {
	parser.Expect(LBRACK)
	body := parser.GetBlockStatements()
	return body
}

func (parser *Parser) ConditionStatement() Node {

	left := parser.ExpressionStatement()

	if parser.Match(DOUBLEEQ) || parser.Match(NEQ) {
		op := parser.PeekToken.Value
		left = NewBinOP(left, parser.ExpressionStatement(), op)
	} else if parser.Match(GT) || parser.Match(GTEQ) {
		op := parser.PeekToken.Value
		left = NewBinOP(left, parser.ExpressionStatement(), op)
	} else if parser.Match(LT) || parser.Match(LTEQ) {
		op := parser.PeekToken.Value
		left = NewBinOP(left, parser.ExpressionStatement(), op)
	}
	return left
}

func (parser *Parser) GetBlockStatements() BlockStatement {

	block := BlockStatement{}
	for !parser.Match(EOF) && !parser.Match(RBRACK) {
		block.Statements = append(block.Statements, parser.GetStatement())
	}

	return block
}

func (parser *Parser) ReaStatement() Node {
	parser.Expect(REA)
	parser.Expect(IDENTIFIER)
	identifier := parser.IdentifierStatement()
	parser.Expect(EQ)
	expression := parser.ExpressionStatement()
	parser.Expect(SEMICOLON)
	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}

	return NewReaStatement(identifier, expression)
}

// init lol = 100 + 100 + 100;
func (parser *Parser) InitStatement() Node {

	parser.Expect(INIT)
	parser.Expect(IDENTIFIER)
	identifier := parser.IdentifierStatement()
	parser.Expect(EQ)
	expression := parser.ExpressionStatement()
	parser.Expect(SEMICOLON)
	if !parser.Match(EOF) {
		parser.Expect(NEWLINE)
	}

	return NewInitStatement(identifier, expression)
}

func (parser *Parser) IdentifierStatement() Node {
	return NewIdentifier(parser.PeekToken.Value)
}

// init issoufre = 10 + 10 + 10;
func (parser *Parser) ExpressionStatement() Node {
	var left Node
	left = parser.Term()

	for parser.Match(PLUS) || parser.Match(MIN) {
		op := parser.PeekToken.Value
		right := parser.Term()
		left = NewBinOP(left, right, op)
	}

	return left
}

func (parser *Parser) Term() Node {
	var left Node
	left = parser.Factor()

	for parser.Match(MUL) || parser.Match(DIV) || parser.Match(MOD) {
		op := parser.PeekToken.Value
		right := parser.Factor()
		left = NewBinOP(left, right, op)
	}

	return left
}

func (parser *Parser) Factor() Node {

	var node Node

	if parser.Match(LPAREN) {
		node = parser.ExpressionStatement()
		parser.Expect(RPAREN)
	} else if parser.Match(IDENTIFIER) {
		node = parser.IdentifierStatement()
	} else {
		node = parser.NumberLiteral()
	}

	return node
}

func (parser *Parser) NumberLiteral() Node {
	parser.Expect(NUMBER)
	num, err := strconv.Atoi(parser.PeekToken.Value)
	if err != nil {
		panic(err)
	}

	return NewNumberLiteral(num)
}
