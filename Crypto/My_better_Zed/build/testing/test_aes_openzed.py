from openzedlib import openzed
import os



FLAG = os.getenv("FLAG", b"pwnme{flagdetest_hyperlong_samere_aaaa}")

password = os.urandom(16)
print(password.hex())

file = openzed.Openzed(b'pwnme', password, 'flag.txt')

print(len(FLAG))
file.encrypt(FLAG)

file.generate_container()
print(file.secure_container)
print(file.secure_container[304:])


print(file.decrypt_container(file.secure_container))

test = openzed.Openzed(b'pwnme', password, 'flag.txt')
print(test.metadata)
test.secure_container = file.secure_container
print(len(test.secure_container[304:]))
print(test.secure_container[4:300-test.padding_len+4])
print(test.decrypt_container(test.secure_container))


