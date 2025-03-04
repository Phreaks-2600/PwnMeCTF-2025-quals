from openzedlib import aes_cbc_zed

import os
import zlib

#pt = b"PWNME{flag}" 
pt = os.getenv("FLAG", b"pwnme{flagdetest_hyperlong_samere_aaaa}")

password = os.urandom(16)
cipher = aes_cbc_zed.AES_CBC_ZED(b"pwnme", password)
print(len(pt))
print(vars(cipher))

ciphertext = cipher.encrypt(pt)

print(f"{ciphertext = }")
print(f"{cipher.decrypt(ciphertext) = }")

cipher = aes_cbc_zed.AES_CBC_ZED(b"pwnme", password)
print(f"{cipher.decrypt(ciphertext) = }")

