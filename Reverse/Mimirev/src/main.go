package main

import (
	"errors"
	"flag"
	"fmt"
	"io/ioutil"
	"log"
	"path/filepath"

	"github.com/Lexterl33t/mimicompiler/compiler"
	"github.com/Lexterl33t/mimicompiler/vm"
)

func checkMimiExtension(filename string) error {
	if filepath.Ext(filename) != ".mimi" {
		return errors.New("the specified file doesn't have the .mimi extension")
	}
	return nil
}

func main() {
	fileFlag := flag.String("file", "", "Path to a .mimi file")
	flag.StringVar(fileFlag, "f", "", "Alias of -file")
	disFlag := flag.Bool("disassemble", false, "Disassemble instead of executing")
	flag.BoolVar(disFlag, "d", false, "Alias of -disassemble")
	EnableDebug := flag.Bool("debug", false, "Enable debug mode")
	flag.BoolVar(EnableDebug, "D", false, "Alias of -debug")
	flag.Parse()

	if *fileFlag == "" {
		log.Fatal("Please provide a .mimi file using -file or -f.")
	}

	if err := checkMimiExtension(*fileFlag); err != nil {
		log.Fatalf("Error: %v\n", err)
	}

	source, err := ioutil.ReadFile(*fileFlag)
	if err != nil {
		log.Fatalf("Cannot read file '%s': %v\n", *fileFlag, err)
	}

	encryptedFlag := "mTfYS2+3UoKAO+gueELVdxNc6QDBwKW1t8uN5Dx/HIGvWb7kMtmLoyt6SB0EIw39"
	targetEntropyHash := "11466b4b07a438fdba619b86088353976073d790344cbf4dae99512028808ecf"
	lifter := compiler.NewLifter(string(source))
	bytecode, err := lifter.Compile()
	if err != nil {
		log.Fatalf("Compilation error: %v\n", err)
	}

	if *disFlag {
		fmt.Println("=== Disassembly ===")
		compiler.Disassembler(bytecode)
		return
	}

	vmCtx := vm.NewVM(bytecode, encryptedFlag, targetEntropyHash)
	if err := vmCtx.Run(); err != nil {
		log.Fatalf("Execution error: %v\n", err)
	}

	if *EnableDebug {
		fmt.Println("Stack  :", vmCtx.GetStack())
		fmt.Println("Storage:", vmCtx.GetStorage())
		fmt.Println("Entropy:", vmCtx.GetEntropy())
	}
}
