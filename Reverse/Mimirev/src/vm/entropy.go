package vm

const (
	PUSH1_STATIC_ENTROPY = iota + 10000
	PUSH2_STATIC_ENTROPY
	PUSH3_STATIC_ENTROPY
	PUSH4_STATIC_ENTROPY
	SLOAD_STATIC_ENTROPY
	SSTORE_STATIC_ENTROPY
	ADD_STATIC_ENTROPY
	SUB_STATIC_ENTROPY
	MULTIPLY_STATIC_ENTROPY
	DIVIDE_STATIC_ENTROPY
	MODOP_STATIC_ENTROPY
	EQOP_STATIC_ENTROPY
	NEQOP_STATIC_ENTROPY
	LTOP_STATIC_ENTROPY
	GTOP_STATIC_ENTROPY
	GTEQOP_STATIC_ENTROPY
	LTEQOP_STATIC_ENTROPY
	JUMP_STATIC_ENTROPY
	JUMPI_STATIC_ENTROPY
)

const PRIME_NUMBER = 1009

func calculateSeuils(entropy float64) (float64, float64) {
	base1 := (entropy * 12.0) / 100.0
	base2 := (entropy * 45.0) / 100.0

	perturb1 := base1 / 3.0
	perturb2 := base2 / 7.0

	intermediate1 := (base1 + perturb1) * 11.0 / 10.0
	intermediate2 := (base2 + perturb2) * 13.0 / 10.0

	seuil1 := intermediate1 + 12345.0
	seuil2 := intermediate2 - 6789.0

	return seuil1, seuil2
}
