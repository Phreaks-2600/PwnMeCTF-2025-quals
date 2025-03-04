package vm

import "fmt"

type Stack struct {
	Stack []int
}

func NewStack() *Stack {
	return &Stack{
		Stack: make([]int, 0),
	}
}

func (st *Stack) Push(value int) {
	st.Stack = append(st.Stack, value)
}

func (st *Stack) Pop(destinations ...*int) error {
	if len(st.Stack) < len(destinations) {
		return fmt.Errorf("not enough elements in the stack")
	}

	for i := range destinations {
		index := len(st.Stack) - 1
		*destinations[i] = st.Stack[index]
		st.Stack = st.Stack[:index]
	}
	return nil
}
