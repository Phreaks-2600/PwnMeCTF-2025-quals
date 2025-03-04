package compiler

import "fmt"

const (
	INIT = iota + 1
	REA
	IDENTIFIER
	NUMBER

	PLUS
	MIN
	DIV
	MUL
	MOD

	EQ
	DOUBLEEQ
	NEQ
	GT
	LT
	GTEQ
	LTEQ

	IF
	ELSE
	WHILE
	BREAK
	VERIFYPROOF
	PRINT
	COMMA

	LBRACK
	RBRACK

	SEMICOLON
	NEWLINE

	LPAREN
	RPAREN

	EOF
)

type Token struct {
	Kind  int
	Value string
}

type Tokens []Token

var tokens_str_table map[int]string = map[int]string{
	INIT:       "INIT",
	IDENTIFIER: "IDENTIFIER",
	REA:        "REA",
	NUMBER:     "NUMBER",
	PLUS:       "PLUS",
	MIN:        "MIN",
	DIV:        "DIV",
	MUL:        "MUL",
	EQ:         "EQ",
	DOUBLEEQ:   "DOUBLEEQ",
	NEQ:        "NEQ",
	GT:         "GT",
	LT:         "LT",
	GTEQ:       "GTEQ",
	LTEQ:       "LTEQ",
	IF:         "IF",
	ELSE:       "ELSE",
	LBRACK:     "LBRACK",
	RBRACK:     "RBRACK",
	SEMICOLON:  "SEMICOLON",
	NEWLINE:    "NEWLINE",
	LPAREN:     "LPAREN",
	RPAREN:     "RPAREN",
	EOF:        "EOF",
}

func (token Token) ToString() string {
	return fmt.Sprintf("TOKEN NAME: %v, Value: %v", tokens_str_table[token.Kind], token.Value)
}

func (tokens Tokens) PrintTokens() {

	for _, token := range tokens {
		fmt.Println(token.ToString())
	}
}

func NewToken(kind int, value string) Token {
	return Token{
		Kind:  kind,
		Value: value,
	}
}
