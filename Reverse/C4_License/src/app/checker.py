#!/usr/bin/python3

import os
import json
import base64
from Crypto.Cipher import ARC4
import ctypes
import zlib

def license_checker(license_key: str, user_to_check: str) -> bool:
    try:
        license_key = json.loads(base64.b64decode(license_key + '==').decode())
    except:
        return False
    
    username = license_key['user']
    if username != user_to_check:
        return False
    
    serial = bytes.fromhex(license_key['serial'])
    
    libc = ctypes.CDLL('libc.so.6')
    libc.srand(zlib.crc32(username.encode()))
    
    n1 = libc.rand() % 0xffff
    n2 = libc.rand() % 0xffff
    
    k = (n1 * n2).to_bytes(4, 'big') 
    cipher = ARC4.new(k)
    plaintext = cipher.decrypt(serial).decode()
    
    if plaintext == 'PwNmE_c4_message!137':
        return True
    return False
    
    
def main():
    print('[C4 License challenge]'.center(50, '-'))
    owen_license = input('Your license for Owen user : ')
    if not license_checker(owen_license, "Owen"):
        print('License key not valid !')
        return 1
    else:
        print('License key valid !')

    jerome_license = input('Your license for Jerome user : ')
    if not license_checker(jerome_license, "Jerome"):
        print('License key not valid !')
        return 1
    else:
        print('License key valid !')

    for i in range(98):
        user = os.urandom(16).hex()
        user_license = input(f'Your license for {user} user : ')
        if not license_checker(user_license, user):
            print('License key not valid !')
            return 1
        else:
            print('License key valid !')
    
    print('Flag : PWNME{8d0f21d2a2989b739673732d8155022b}')
      
if __name__ == '__main__':
    main()