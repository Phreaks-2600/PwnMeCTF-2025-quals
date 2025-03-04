package compiler

import (
	"errors"
	"unicode"
)

type Lexer struct {
	Source      []byte
	Cursor      int
	LenSource   int
	CurrentChar byte
	EOF         bool
}

func NewLexer(source string) *Lexer {
	return &Lexer{
		Source:      []byte(source),
		Cursor:      0,
		LenSource:   len(source),
		CurrentChar: byte(source[0]),
		EOF:         false,
	}
}

func (lex *Lexer) NextChar() {
	if lex.Cursor+1 < lex.LenSource {
		lex.Cursor++
		lex.CurrentChar = lex.Source[lex.Cursor]
	} else {
		lex.EOF = true
	}
}

func (lex *Lexer) PeekChar() byte {
	if lex.Cursor+1 < lex.LenSource {
		return lex.Source[lex.Cursor+1]
	} else {
		return 0
	}
}

func (lex *Lexer) DescendChar() {
	if lex.Cursor > 0 {
		lex.Cursor--
	}
}

func (lex *Lexer) ClearWhiteSpace() {
	for lex.CurrentChar == 32 || lex.CurrentChar == 13 {
		lex.NextChar()
	}
}

func (lex *Lexer) Tokenize() (Tokens, error) {

	tokens := Tokens{}

	for !lex.EOF {

		lex.ClearWhiteSpace()

		switch lex.CurrentChar {
		case '+', '-', '/', '*', '%':
			tokens = append(tokens, lex.TokenizeArithmeticOperators())
		case ',':
			tokens = append(tokens, NewToken(COMMA, ","))
		case 10:
			tokens = append(tokens, NewToken(NEWLINE, "NEWLINE"))
		case '=':
			tokens = append(tokens, lex.TokenizeEqualOperator())
		case '!':
			tokens = append(tokens, lex.TokenizeNotEqualOperator())
		case '<':
			tokens = append(tokens, lex.TokenizeLessOperator())
		case '>':
			tokens = append(tokens, lex.TokenizeGreaterOperator())
		case '{':
			tokens = append(tokens, NewToken(LBRACK, "{"))
		case '}':
			tokens = append(tokens, NewToken(RBRACK, "}"))
		case '(':
			tokens = append(tokens, NewToken(LPAREN, "("))
		case ')':
			tokens = append(tokens, NewToken(RPAREN, ")"))
		case ';':
			tokens = append(tokens, NewToken(SEMICOLON, ";"))
		default:
			if unicode.IsDigit(rune(lex.CurrentChar)) {
				tokens = append(tokens, lex.TokenizeDigit())
			} else if unicode.IsLetter(rune(lex.CurrentChar)) {
				tokens = append(tokens, lex.TokenizeIdentifier())
			} else {
				return tokens, errors.New("Unknow Token bro")
			}
		}

		lex.NextChar()
	}

	tokens = append(tokens, NewToken(EOF, "EOF"))
	return tokens, nil
}

func (lex *Lexer) TokenizeArithmeticOperators() Token {

	token := Token{}
	switch lex.CurrentChar {
	case '+':
		token = NewToken(PLUS, "+")
	case '-':
		token = NewToken(MIN, "-")
	case '/':
		token = NewToken(DIV, "/")
	case '*':
		token = NewToken(MUL, "*")
	case '%':
		token = NewToken(MOD, "%")
	}

	return token
}

func (lex *Lexer) TokenizeNotEqualOperator() Token {

	if lex.CurrentChar == '!' && lex.PeekChar() == '=' {
		lex.NextChar()
		return NewToken(NEQ, "!=")
	} else {
		panic("Incorrect utilisation of '!' operator")
	}
}

func (lex *Lexer) TokenizeEqualOperator() Token {

	if lex.CurrentChar == '=' && lex.PeekChar() == '=' {
		lex.NextChar()
		return NewToken(DOUBLEEQ, "==")
	}

	return NewToken(EQ, "=")
}

func (lex *Lexer) TokenizeGreaterOperator() Token {
	if lex.CurrentChar == '>' && lex.PeekChar() == '=' {
		lex.NextChar()
		return NewToken(GTEQ, ">=")
	}

	return NewToken(GT, ">")
}

func (lex *Lexer) TokenizeLessOperator() Token {
	if lex.CurrentChar == '<' && lex.PeekChar() == '=' {
		lex.NextChar()
		return NewToken(LTEQ, "<=")
	}

	return NewToken(LT, "<")
}

func (lex *Lexer) TokenizeDigit() Token {

	number := ""
	for unicode.IsDigit(rune(lex.CurrentChar)) && !lex.EOF {
		number += string(lex.CurrentChar)
		lex.NextChar()
	}

	lex.DescendChar()

	return NewToken(NUMBER, number)
}

func (lex *Lexer) TokenizeIdentifier() Token {

	identifier := ""
	var token Token
	for unicode.IsLetter(rune(lex.CurrentChar)) && !lex.EOF {
		identifier += string(lex.CurrentChar)
		lex.NextChar()
	}
	lex.DescendChar()

	if identifier == "if" {
		token = NewToken(IF, identifier)
	} else if identifier == "else" {
		token = NewToken(ELSE, identifier)
	} else if identifier == "init" {
		token = NewToken(INIT, identifier)
	} else if identifier == "rea" {
		token = NewToken(REA, identifier)
	} else if identifier == "while" {
		token = NewToken(WHILE, identifier)
	} else if identifier == "break" {
		token = NewToken(BREAK, identifier)
	} else if identifier == "verifyProof" {
		token = NewToken(VERIFYPROOF, identifier)
	} else if identifier == "print" {
		token = NewToken(PRINT, identifier)
	} else {
		token = NewToken(IDENTIFIER, identifier)
	}
	return token
}
