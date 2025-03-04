package vm

import (
	"crypto/aes"
	"crypto/sha256"
	b64 "encoding/base64"
	"errors"
	"fmt"

	"github.com/Lexterl33t/mimicompiler/compiler"
)

type VM struct {
	Stack             *Stack
	Entropy           int64
	IP                int
	Bytecode          []byte
	Storage           map[int]int
	EncryptedFlag     []byte
	TargetEntropyHash string
}

func NewVM(bytecode []byte, flagcipher string, targetEntropyHash string) *VM {

	sDec, _ := b64.StdEncoding.DecodeString(flagcipher)
	return &VM{
		Stack:             NewStack(),
		Entropy:           0,
		IP:                0,
		Bytecode:          bytecode,
		Storage:           make(map[int]int),
		EncryptedFlag:     sDec,
		TargetEntropyHash: targetEntropyHash,
	}
}

func (vm *VM) GetEntropy() int64 {
	return vm.Entropy
}

func (vm *VM) GetStack() []int {
	return vm.Stack.Stack
}

func (vm *VM) GetStorage() map[int]int {
	return vm.Storage
}

func unpadPlaintext(plaintext []byte) ([]byte, error) {
	paddingLength := int(plaintext[len(plaintext)-1])
	if paddingLength > len(plaintext) || paddingLength == 0 {
		return nil, errors.New("invalid padding")
	}

	for _, b := range plaintext[len(plaintext)-paddingLength:] {
		if int(b) != paddingLength {
			return nil, errors.New("invalid padding")
		}
	}

	return plaintext[:len(plaintext)-paddingLength], nil
}

func decryptFlag(ciphertext []byte, key []byte) (string, error) {

	block, err := aes.NewCipher(key)
	if err != nil {
		return "", fmt.Errorf("failed to create AES cipher: %v", err)
	}

	if len(ciphertext)%aes.BlockSize != 0 {
		return "", errors.New("ciphertext is not a multiple of the block size")
	}

	plaintext := make([]byte, len(ciphertext))
	for i := 0; i < len(ciphertext); i += aes.BlockSize {
		block.Decrypt(plaintext[i:i+aes.BlockSize], ciphertext[i:i+aes.BlockSize])
	}

	unpadded, err := unpadPlaintext(plaintext)
	if err != nil {
		return "", fmt.Errorf("failed to unpad plaintext: %v", err)
	}

	return string(unpadded), nil
}

func (vm *VM) Run() error {

	for vm.IP < len(vm.Bytecode) {

		switch vm.Bytecode[vm.IP] {
		case compiler.PUSH1:
			err := vm.Push(1)
			if err != nil {
				return err
			}
		case compiler.PUSH2:
			err := vm.Push(2)
			if err != nil {
				return err
			}
		case compiler.PUSH3:
			err := vm.Push(3)
			if err != nil {
				return err
			}
		case compiler.PUSH4:
			err := vm.Push(4)
			if err != nil {
				return err
			}
		case compiler.SLOAD:
			err := vm.Ssload()
			if err != nil {
				return err
			}
		case compiler.SSTORE:
			err := vm.Sstore()
			if err != nil {
				return err
			}
		case compiler.ADD:
			err := vm.Add()
			if err != nil {
				return err
			}
		case compiler.SUB:
			err := vm.Sub()
			if err != nil {
				return err
			}
		case compiler.MULTIPLY:
			err := vm.Mul()
			if err != nil {
				return err
			}
		case compiler.DIVIDE:
			err := vm.Div()
			if err != nil {
				return err
			}
		case compiler.MODOP:
			err := vm.Mod()
			if err != nil {
				return err
			}
		case compiler.EQOP:
			err := vm.Eq()
			if err != nil {
				return err
			}
		case compiler.NEQOP:
			err := vm.Neq()
			if err != nil {
				return err
			}
		case compiler.JUMP:
			err := vm.Jump()
			if err != nil {
				return err
			}
		case compiler.JUMPI:
			err := vm.Jumpi()
			if err != nil {
				return err
			}
		case compiler.GTOP:
			err := vm.Gt()
			if err != nil {
				return err
			}
		case compiler.GTEQOP:
			err := vm.Gteq()
			if err != nil {
				return err
			}
		case compiler.LTOP:
			err := vm.Lt()
			if err != nil {
				return err
			}
		case compiler.LTEQOP:
			err := vm.Lteq()
			if err != nil {
				return err
			}
		case compiler.VERIFYPROOFOP:
			err := vm.VerifyProof()
			if err != nil {
				return err
			}
		case compiler.PRINTOP:
			err := vm.Print()
			if err != nil {
				return err
			}
		case compiler.JUMPDEST:
			vm.IP++
		}
	}

	return nil
}

func (vm *VM) Push(n int) error {

	if vm.IP+n >= len(vm.Bytecode) {
		return errors.New("PC overflow")
	}

	vm.IP++
	end := vm.IP + n
	if end > len(vm.Bytecode) {
		return errors.New("PC overflow during bytecode slicing")
	}

	switch n {
	case 1:
		vm.Entropy += PRIME_NUMBER * PUSH1_STATIC_ENTROPY
	case 2:
		vm.Entropy += PRIME_NUMBER * PUSH2_STATIC_ENTROPY
	case 3:
		vm.Entropy += PRIME_NUMBER * PUSH3_STATIC_ENTROPY
	case 4:
		vm.Entropy += PRIME_NUMBER * PUSH4_STATIC_ENTROPY
	}

	number := compiler.BytesToIntBigEndian(vm.Bytecode[vm.IP:end])
	vm.Stack.Push(number)

	vm.IP += n

	return nil
}

func (vm *VM) Sstore() error {
	if vm.IP+1 >= len(vm.Bytecode) {
		return errors.New("PC overflow")
	}

	vm.IP++
	end := vm.IP + 1
	if end > len(vm.Bytecode) {
		return errors.New("PC overflow during bytecode slicing")
	}

	offsetStorage := compiler.BytesToIntBigEndian(vm.Bytecode[vm.IP:end])

	var value int

	err := vm.Stack.Pop(&value)
	if err != nil {
		return err
	}

	vm.Entropy += PRIME_NUMBER * SSTORE_STATIC_ENTROPY

	vm.Storage[offsetStorage] = value
	vm.IP += 1

	return nil

}

func (vm *VM) Ssload() error {
	if vm.IP+1 >= len(vm.Bytecode) {
		return errors.New("PC overflow")
	}

	vm.IP++

	end := vm.IP + 1
	if end > len(vm.Bytecode) {
		return errors.New("PC overflow during bytecode slicing")
	}

	offsetStorage := compiler.BytesToIntBigEndian(vm.Bytecode[vm.IP:end])

	if value, ok := vm.Storage[offsetStorage]; !ok {
		return errors.New("No variable associated with this offset in storage")
	} else {
		vm.Stack.Push(value)
	}

	vm.Entropy += PRIME_NUMBER * SLOAD_STATIC_ENTROPY

	vm.IP += 1
	return nil
}

func (vm *VM) Add() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	vm.Entropy += PRIME_NUMBER * ADD_STATIC_ENTROPY
	vm.Stack.Push(a + b)

	vm.IP += 1

	return nil
}

func (vm *VM) Sub() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}
	vm.Entropy += PRIME_NUMBER * SUB_STATIC_ENTROPY
	vm.Stack.Push(b - a)
	vm.IP += 1

	return nil
}

func (vm *VM) Mul() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}
	vm.Entropy += PRIME_NUMBER * MULTIPLY_STATIC_ENTROPY
	vm.Stack.Push(a * b)
	vm.IP += 1

	return nil
}

func (vm *VM) Div() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	result := 0

	if a == 0 {
		a = b
	}

	if b == 0 {
		result = 0
	} else {
		result = b / a
	}
	vm.Entropy += PRIME_NUMBER * DIVIDE_STATIC_ENTROPY
	vm.Stack.Push(result)

	vm.IP += 1

	return nil
}

func (vm *VM) Mod() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	vm.Entropy += PRIME_NUMBER * MODOP_STATIC_ENTROPY
	vm.Stack.Push(b % a)
	vm.IP += 1

	return nil
}

func (vm *VM) Eq() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if a == b {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * EQOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1

	return nil
}

func (vm *VM) Neq() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if a != b {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * NEQOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1
	return nil
}

func (vm *VM) Gt() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if b > a {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * GTOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1

	return nil
}

func (vm *VM) Lt() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if b < a {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * LTOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1

	return nil
}

func (vm *VM) Gteq() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if b >= a {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * GTEQOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1

	return nil
}

func (vm *VM) Lteq() error {

	var a, b int

	err := vm.Stack.Pop(&a, &b)
	if err != nil {
		return err
	}

	ret := 0

	if b <= a {
		ret = 1
	}
	vm.Entropy += PRIME_NUMBER * LTEQOP_STATIC_ENTROPY
	vm.Stack.Push(ret)
	vm.IP += 1

	return nil
}

// Controls

func (vm *VM) Jumpi() error {
	var condition, offset int

	if err := vm.Stack.Pop(&offset, &condition); err != nil {
		return err
	}

	if condition == 0 {
		vm.IP++
		return nil
	}

	if offset >= len(vm.Bytecode) {
		return errors.New("PC overflow")
	}

	if condition != 1 {
		return errors.New("Invalid condition boolean")
	}
	vm.Entropy += PRIME_NUMBER * JUMPI_STATIC_ENTROPY
	vm.IP = offset

	return nil
}

func (vm *VM) Jump() error {

	var offset int
	if err := vm.Stack.Pop(&offset); err != nil {
		return err
	}

	if offset >= len(vm.Bytecode) {

		return errors.New("PC overflow")
	}
	vm.Entropy += PRIME_NUMBER * JUMP_STATIC_ENTROPY
	vm.IP = offset

	return nil
}

func (vm *VM) VerifyProof() error {

	var x, y int
	err := vm.Stack.Pop(&y)
	if err != nil {
		return fmt.Errorf("Failed to pop value y: %v", err)
	}

	err = vm.Stack.Pop(&x)
	if err != nil {
		return fmt.Errorf("Failed to pop value x: %v", err)
	}

	const targetSum = 314159
	const targetModulo = 1048573
	const targetConstant = 273262

	if x+y != targetSum {
		return errors.New("VerifyProof failed")
	}

	result := (x*x + y*y*y - x*y) % targetModulo
	if result != targetConstant {
		return fmt.Errorf("VerifyProof failed")
	}

	fmt.Println("VerifyProof passed successfully. Flag unlocked!")

	combined := fmt.Sprintf("%d:%d", x, y)
	hash := sha256.Sum256([]byte(combined))
	key := hash[:16]

	flag, err := decryptFlag(vm.EncryptedFlag, key)
	if err != nil {
		return fmt.Errorf("Error decrypting flag: %v\n", err)
	}

	fmt.Println("Flag:", flag)
	vm.IP += 1
	return nil
}

func (vm *VM) Print() error {

	var value int

	err := vm.Stack.Pop(&value)
	if err != nil {
		return err
	}

	fmt.Println(value)
	vm.IP += 1

	return nil
}
