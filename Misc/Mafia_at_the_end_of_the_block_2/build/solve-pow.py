import hashlib
import sys
import time

preimage_prefix = sys.argv[1].encode()
bits = int(sys.argv[2])
start_time = time.time()

for i in range(0, 1 << 32):
    your_input = str(i).encode()
    preimage = preimage_prefix + your_input
    digest = hashlib.sha256(preimage).digest()
    digest_int = int.from_bytes(digest, "big")
    if digest_int < (1 << (256 - bits)):
        print(f"{your_input = }")
        print(f"{digest.hex() = }")
        print(f"{time.time() - start_time = }")
        break
