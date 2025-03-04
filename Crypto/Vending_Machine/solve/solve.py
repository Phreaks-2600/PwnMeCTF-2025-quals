from sage.all import *
from pwn import process, remote
from hashlib import sha3_256
from Crypto.Util.number import *
from Crypto.Util.Padding import unpad
from Crypto.Cipher import AES
from json import dumps
from tqdm import tqdm
import ast

n = 0xf1fd178c0b3ad58f10126de8ce42435b53dc67e140d2bf941ffdd459c6d655e1

# proc = process("./server.py")
proc = remote("localhost", 4000)
proc.recvuntil(b"format: ")

signatures = []
forged_signatures = []
used_credits = 0

# bytes() with single quoted names in json format
def json_read(inp):
    inp = inp.strip()
    inp = ast.literal_eval(inp.decode())
    return inp

def show_credits():
    proc.sendline(dumps({"action": "show_credits"}))
    res = json_read(proc.recvline())
    clean()
    return res

def show_currency():
    proc.sendline(dumps({"action": "show_currency"}))
    res = json_read(proc.recvline())
    clean()
    return res

def get_encrypted_flag():
    proc.sendline(dumps({"action": "get_encrypted_flag"}))
    res = json_read(proc.recvline())
    clean()
    return res

def get_new_signatures(alea_1, alea_2):
    proc.sendline(dumps({"action": "get_signatures", "alea_1": alea_1, "alea_2": alea_2}))
    res = json_read(proc.recvline())
    clean()
    return res

def wrapper_get_new_signatures():
    global used_credits
    global forged_signatures
    global signatures
    alea_1 = "-1"
    alea_2 = "-2"
    new_signatures = get_new_signatures(alea_1, alea_2)
    used_credits += 1
    for i, (r, s) in enumerate(new_signatures["signatures"]):
        m = sha3_256(b"this is my lovely loved distributed item " + str(i+10*used_credits).encode()).digest()        
        e = int.from_bytes(sha3_256(m).digest(), "big")
        signatures.append((r, s, e))
        forged_signatures.append((m.hex(), r, n-s))
    return signatures

def buy_credit():
    global forged_signatures
    currency = int(show_currency()["currency"])
    proc.sendline(dumps({"action": "buy_credit", "owner_proofs": forged_signatures[:currency]}))
    forged_signatures = forged_signatures[currency:]
    res = json_read(proc.recvline())
    clean()    
    return res

def clean():
    proc.recvuntil(b"format: ")

def decrypt_flag(private_key):
    encrypted_flag = bytes.fromhex(get_encrypted_flag()["encrypted_flag"])
    iv = bytes.fromhex(get_encrypted_flag()["iv"])
    key = sha3_256(private_key.to_bytes(32, "big")).digest()[:16]
    cipher = AES.new(key, IV=iv, mode=AES.MODE_CBC)
    return cipher.decrypt(encrypted_flag)


wrapper_get_new_signatures()
buy_credit()
buy_credit()
wrapper_get_new_signatures()
wrapper_get_new_signatures()
buy_credit()
wrapper_get_new_signatures()
buy_credit()
wrapper_get_new_signatures()
buy_credit()
wrapper_get_new_signatures()

NB_MSG = len(signatures)

last_r = signatures[-1][0]
last_s = signatures[-1][1]
inv_last_s = inverse(last_s, n)
last_e = signatures[-1][2]

t_values = []
a_values = []

for r, s, e in signatures[:-1]:
    inv_s = inverse(s, n) 
    t = ( (inv_s * r) - (last_r * inv_last_s) ) % n  
    a = ( (inv_s * e) - (last_e * inv_last_s) ) % n 
    t_values.append(t)
    a_values.append(a)

# sage: m = 60
# sage: int(((log(n,2) * (m-1)) / m) - (log(m,2)/2)) from https://eprint.iacr.org/2019/023.pdf
# 248

B = 2**248
f = QQ
NB_MSG -= 1 # last signature used to cancel the unknown prefix

diagonal_block = diagonal_matrix(f, [n] * NB_MSG)
t_vector = Matrix(f, t_values)
a_vector = Matrix(f, a_values)

# Assemble the full matrix
M = block_matrix(f, [
    [diagonal_block, zero_matrix(f, NB_MSG, 2)],
    [t_vector, Matrix(f, [f(B) / f(n), 0])],
    [a_vector, Matrix(f, [0, f(B)])],
])

M_reduced = M.LLL()
s0 = signatures[0][1]
r0 = signatures[0][0]
e0 = signatures[0][2]

for i in range(len(list(M_reduced))):
    k_diff_test = int(M_reduced[i][0])
    d_test = inverse((last_r*s0 - r0*last_s), n) * (e0*last_s - last_e*s0 - s0*last_s*k_diff_test) % n
    test_dec_flag = decrypt_flag(d_test)
    if b"pwnme{" in test_dec_flag.lower():
        print(unpad(test_dec_flag, 16))
        break

proc.interactive()
