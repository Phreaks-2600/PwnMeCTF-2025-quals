package compiler

type Node interface {
	GetNode() Node
}

type Program struct {
	Block BlockStatement
}

type BlockStatement struct {
	Statements []Node
}

type InitialiseStatement struct {
	KeyWord    string
	Identifier Node
	AssignKey  string
	Expression Node
}

func NewInitStatement(identifier Node, expression Node) InitialiseStatement {
	return InitialiseStatement{
		KeyWord:    "init",
		Identifier: identifier,
		AssignKey:  "=",
		Expression: expression,
	}
}

func (initSt InitialiseStatement) GetNode() Node {
	return initSt
}

type Identifier struct {
	Ident string
}

func NewIdentifier(ident string) Node {
	return Identifier{
		Ident: ident,
	}
}

func (id Identifier) GetNode() Node {
	return id
}

type ReassignmentStatement struct {
	KeyWord    string
	Identifier Node
	AssignKey  string
	Expression Node
}

func (reaSt ReassignmentStatement) GetNode() Node {
	return reaSt
}

func NewReaStatement(identifier Node, expression Node) Node {
	return ReassignmentStatement{
		KeyWord:    "rea",
		Identifier: identifier,
		Expression: expression,
	}
}

type IfStatement struct {
	KeyWord    string
	Condition  Node
	Consequent BlockStatement
	Alternate  BlockStatement
}

func (ifState IfStatement) GetNode() Node {
	return ifState
}

func NewIfStatement(condition Node, consequent BlockStatement, alternate BlockStatement) Node {
	return IfStatement{
		KeyWord:    "if",
		Condition:  condition,
		Consequent: consequent,
		Alternate:  alternate,
	}
}

type NumberLiteral struct {
	Number int
}

func (nl NumberLiteral) GetNode() Node {
	return nl
}

func NewNumberLiteral(number int) Node {
	return NumberLiteral{
		Number: number,
	}
}

type BinOP struct {
	Lhs Node
	Rhs Node
	Op  string
}

func (bop BinOP) GetNode() Node {
	return bop
}

func NewBinOP(lhs Node, rhs Node, op string) Node {
	return BinOP{
		Lhs: lhs,
		Rhs: rhs,
		Op:  op,
	}
}

type WhileStatement struct {
	KeyWord   string
	Condition Node
	Body      BlockStatement
}

func (whst WhileStatement) GetNode() Node {
	return whst
}

func NewWhileStatement(condition Node, body BlockStatement) Node {
	return WhileStatement{
		KeyWord:   "while",
		Condition: condition,
		Body:      body,
	}
}

type BreakStatement struct {
	KeyWord string
}

func (brst BreakStatement) GetNode() Node {
	return brst
}

func NewBreakStatement() Node {
	return BreakStatement{
		KeyWord: "break",
	}
}

type VerifyProofStatement struct {
	KeyWord   string
	Arguments []Node
}

func (vprfst VerifyProofStatement) GetNode() Node {
	return vprfst
}

func NewVerifyProofStatement(arguments []Node) Node {
	return VerifyProofStatement{
		KeyWord:   "verifyProof",
		Arguments: arguments,
	}
}

type PrintStatement struct {
	KeyWord    string
	Expression Node
}

func (ptstmt PrintStatement) GetNode() Node {
	return ptstmt
}

func NewPrintStatement(expression Node) Node {
	return PrintStatement{
		KeyWord:    "print",
		Expression: expression,
	}
}
