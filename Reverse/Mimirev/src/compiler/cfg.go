package compiler

type CFGNode struct {
	ID         string
	Statements []Node
	Condition  Node
	Successors []string
}

type CFG struct {
	Nodes map[string]*CFGNode
	Entry string
	Exit  string
}

func NewCFG() *CFG {
	return &CFG{
		Nodes: make(map[string]*CFGNode),
	}
}

func (cfg *CFG) AddNode(node *CFGNode) {
	cfg.Nodes[node.ID] = node
}

func (cfg *CFG) Build(program Program, newLabel func(prefix string) string) {
	entryNode := newLabel("ENTRY")
	exitNode := newLabel("EXIT")

	cfg.Entry = entryNode
	cfg.Exit = exitNode

	cfg.traverseBlock(program.Block, entryNode, exitNode, newLabel)
}

func (cfg *CFG) traverseBlock(block BlockStatement, currentID, exitID string, newLabel func(prefix string) string) string {
	var currentStatements []Node

	for _, statement := range block.Statements {
		switch stmt := statement.(type) {

		case InitialiseStatement, ReassignmentStatement:
			currentStatements = append(currentStatements, stmt)

		case IfStatement:
			if len(currentStatements) > 0 {
				nodeID := newLabel("BLOCK")
				cfg.AddNode(&CFGNode{
					ID:         currentID,
					Statements: currentStatements,
					Successors: []string{nodeID},
				})
				currentID = nodeID
				currentStatements = nil
			}

			conditionNodeID := newLabel("CONDITION")
			cfg.AddNode(&CFGNode{
				ID:         conditionNodeID,
				Condition:  stmt.Condition,
				Successors: []string{},
			})

			cfg.Nodes[currentID] = &CFGNode{
				ID:         currentID,
				Statements: nil,
				Successors: []string{conditionNodeID},
			}

			trueBranchID := newLabel("TRUE_BRANCH")
			falseBranchID := newLabel("FALSE_BRANCH")
			endIfID := newLabel("END_IF")

			cfg.Nodes[conditionNodeID].Successors = append(cfg.Nodes[conditionNodeID].Successors, trueBranchID, falseBranchID)

			cfg.traverseBlock(stmt.Consequent, trueBranchID, endIfID, newLabel)

			if len(stmt.Alternate.Statements) > 0 {
				cfg.traverseBlock(stmt.Alternate, falseBranchID, endIfID, newLabel)
			} else {
				cfg.AddNode(&CFGNode{
					ID:         falseBranchID,
					Statements: nil,
					Successors: []string{endIfID},
				})
			}

			currentID = endIfID
		}
	}

	if len(currentStatements) > 0 {
		cfg.AddNode(&CFGNode{
			ID:         currentID,
			Statements: currentStatements,
			Successors: []string{exitID},
		})
	} else {
		cfg.AddNode(&CFGNode{
			ID:         currentID,
			Statements: nil,
			Successors: []string{exitID},
		})
	}

	return exitID
}
