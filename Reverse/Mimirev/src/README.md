# MIMILANG

## Compiler

### Grammar

```ebnf
program = { statement }

statement = { initialiseStatement | ReassignmentStatement | IfStatement | WhileStatement | BreakStatement | VerifyProofStatement | PrintStatement }, newline

VerifyProofStatement = "verifyProof", "(", argumentsList, ")", terminator
PrintStatement = "print", "(", expression, ")", terminator

argumentsList = [ ( expression ), "," ];
WhileStatement = "while", condition, "{", blockStatement, "}"
BreakStatement = "break", terminator

initialiseStatement = "init", identifier, "=", expression, terminator

identifier = letter, { letter }

letter = "a" | "b" | "c" | "d" | "e" | "f" | "g" | "h" | "i" | "j" | "k" | "l" | "m" | "n" | "o" | "p" | "q" | "r" | "s" | "t" | "u" | "v" | "w" | "x" | "y" | "z"

expression = term, ('+' | '-'), term | term

term = factor, ('*' | '/' | '%'), factor | factor

factor = (int_lit | identifier) | "(", expression, ")" | "-", factor

int_lit = digit, { digit }

digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9"

ReassignmentStatement = "rea", identifier, "=", expression, terminator

IfStatement = "if", condition, "{", blockStatement, "}", elseStatement?

elseStatement = "else", "{", blockStatement, "}"

condition = expressionWithComparison

expressionWithComparison = expression, operatorComparisons, expression
                          | expression

operatorComparisons = "==" | "!=" | "<" | ">" | "<=" | ">="

blockStatement = statement, { statement }

newline = "\n"

terminator = ";"

```

## VM
### Initialise variable

**High level view**
```
init magic = 1000;
init test = 100;
init lol = test;
```
**Assembly view**
```
PUSH 1000
STORE magic

PUSH 100
STORE test

LOAD test
STORE lol
```

### Reassign variable

**High level view**
```
init magic = 1000;
rea magic = 100;
```

**Assembly view**
```
PUSH 1000
STORE magic

PUSH 100
STORE magic
```

**High level view**
```
init magic = 1000;
init test = 100;
rea magic = test;
```

**Assembly view**
```
PUSH 1000
STORE magic

PUSH 100
STORE test

LOAD test
STORE magic
```

### Operations variable

**High level view**
```
init magic = 1000;
init test = 100 + 100 * 300;
rea magic = magic + test;
```

**Assembly view**
```
PUSH 1000
STORE magic

PUSH 100
PUSH 300
MUL
PUSH 100
ADD
STORE test

LOAD test
LOAD magic
add
STORE magic
```

### Controls
#### Conditions

```
PUSH 100
SSTORE magic

SLOAD magic
PUSH 100

```


### Instructions set

**0x00 PUSH1** -> PUSH 1 byte to the stack

**0x01 PUSH2** -> PUSH 2 bytes to the stack

**0x02 PUSH3** -> PUSH 3 bytes to the stack

**0x04 PUSH4** -> PUSH 4 bytes to the stack

**0x05 SLOAD** -> Load value from the dest storage and push it to the stack

**0x06 SSTORE** -> Store a value from the stack to the dest storage

**0x07 ADD** -> pop 2 values from the stack and add it. The result is pushed to the stack

**0x08 SUB** -> pop 2 values from the stack and sub it. The result is pushed to the stack

**0x09 DIV** -> pop 2 values from the stack and div it. The result is pushed to the stack

**0x0A MUL** -> pop 2 values from the stack and div it. The result is pushed to the stack

**0x0B JUMP** -> pop 1 offset from the stack and jump to the destination if is a valid destination.

**0x0C JUMPI** pop 2 values from the stack (result condition && offset) if the condition are true the program jump to the offset

**0x0D EQ** -> pop 2 values from the stack and compare the equality and push it to the stack.

**0x0E NEQ** -> pop 2 values from the stack and compare the non equality and push it to the stack

**0x0F GT** -> pop 2 values from the stack and compare the greater and push it to the stack

**0x10 GTEQ** -> pop 2 values from the stack and compare the greater equal and push it to the stack

**0x11 LT** -> pop2 values from the stack and compare the less and push it to the stack

**0x12 LTEQ** -> pop 2 values from the stack and compare the less equal and push it to the stack


## Language

### Initialise variable
```
init magic = 1000;
init test = 100;
init lol = test;
```

### Reassign variable
```
init magic = 1000;
rea magic = 100;
```
```
init magic = 1000;
init test = 100;
rea magic = test;
```

### Controls

#### Conditions

**High level view**
```
init magic = 1000;
init test = 0;
if magic == 1000 {
    rea test = 10;
}
```

**Assembly View**
```
PUSH 1000
STORE magic

PUSH 0
STORE test

LOAD magic
PUSH 1000
EQ
PUSH TRUE_ETIQUETTE
JUMPIF_TRUE
PUSH END_IF_ETIQUETTE
JUMP

TRUE_ETIQUETTE:
PUSH 10
STORE test
PUSH END_IF_ETIQUETTE
JUMP

END_IF_ETIQUETTE:
...
```

**High Level View**
```
init magic = 1000;
init test = 0;
if magic == 1000 {
    rea test = 10;
} else {
    rea test = 20;
}
```

**Assembly view**
```
PUSH 1000
STORE magic

PUSH 0
STORE test

LOAD magic
PUSH 1000
EQ
PUSH TRUE_ETIQUETTE
JUMPIF_TRUE
PUSH ELSE_ETIQUETTE
JUMP

TRUE_ETIQUETTE:
PUSH 10
STORE test
PUSH END_IF_ETIQUETTE
JUMP


ELSE_ETIQUETTE:
PUSH 20
STORE test
PUSH END_IF_ETIQUETTE
JUMP

END_IF_ETIQUETTE:
...
```

### Operations variable
```
init magic = 1000;
init test = 100 + 100 * 300;
rea = magic + test;
```

### Controls

#### Conditions
```
init magic = 1000;
init test = 0;
if magic == 1000 {
    rea test = 10;
}
```

```
init magic = 1000;
init test = 0;
if magic == 1000 {
    rea test = 10;
} else {
    rea test = 20;
}
```
#### While loop

```
init i = 0;

while i < 10 {
    i+=1;
}
```
