package compiler

func CountBits(value int) int {

	if value == 0 {
		return 1
	}

	bits := 0

	for value > 0 {
		bits++
		value >>= 1
	}
	return bits
}

func NBBytesFromInt(value int) int {

	return (CountBits(value) + 7) / 8
}

func IntToBytesBigEndian(n int) []byte {
	var bytes []byte

	for n > 0 {
		bytes = append([]byte{byte(n & 0xFF)}, bytes...)
		n >>= 8
	}

	if len(bytes) == 0 {
		bytes = append(bytes, 0)
	}

	return bytes
}

func IntToBytesBigEndianTwo(value int) []byte {
	return []byte{
		byte(value >> 24),
		byte(value >> 16),
		byte(value >> 8),
		byte(value),
	}
}

func BytesToIntBigEndian(bytes []byte) int {
	var n int
	for _, b := range bytes {
		n = (n << 8) | int(b)
	}
	return n
}

func insertByteAt(b []byte, index int, newVal byte) []byte {
	if index < 0 {
		index = 0
	}
	if index > len(b) {
		index = len(b)
	}

	return append(b[:index], append([]byte{newVal}, b[index:]...)...)
}
