from Crypto.Cipher import AES
from Crypto.Util.Padding import pad
from Crypto.Protocol.KDF import PBKDF2
from Crypto.Hash import SHA256


def xor(a: bytes, b: bytes) -> bytes:
	return bytes(x^y for x,y in zip(a,b))

class AES_CBC_ZED:
	def __init__(
		self, 
		user : str, 
		password : str, 
		iv : bytes
	):
		self.user = user
		self.iv = iv
		self.password = password
		self.derive_password()

	def encrypt(self, plaintext: bytes):
		iv = self.iv
		ciphertext = b""
		ecb_cipher = AES.new(key=self.key, mode=AES.MODE_ECB)
		
		
		for pos in range(0, len(plaintext), 16):
			chunk = plaintext[pos:pos+16]
			
			# AES CFB for the last block or if there is only one block
			if len(plaintext[pos+16:pos+32]) == 0 :
				#if plaintext length <= 16, iv = self.iv
				if len(plaintext) <= 16 :
					prev=iv
				# else, iv = previous ciphertext
				else:
					prev=ciphertext[pos-16:pos]
					
				prev = ecb_cipher.encrypt(prev)
				ciphertext += xor(chunk, prev)
			
			# AES CBC for the n-1 firsts block
			elif not ciphertext:
				xored = bytes(xor(plaintext, iv))
				ciphertext += ecb_cipher.encrypt(xored)
				
			else:
				xored = bytes(xor(chunk, ciphertext[pos-16:pos]))
				ciphertext += ecb_cipher.encrypt(xored)

		return ciphertext


	def decrypt(self, ciphertext: bytes):
		plaintext = b""
		ecb_cipher = AES.new(key=self.key, mode=AES.MODE_ECB)
		iv = self.iv
		
		for pos in range(0, len(ciphertext), 16):
			chunk = ciphertext[pos:pos+16]
			
			# AES CFB for the last block or if there is only one block
			if len(ciphertext[pos+16:pos+32]) == 0 :
				
				#if plaintext length <= 16, iv = self.iv
				if len(ciphertext) <= 16 :
					prev = iv
				# else, iv = previous ciphertext
				else:
					prev = ciphertext[pos-16:pos]

				prev = ecb_cipher.encrypt(prev)
				plaintext += xor(prev, chunk)
				
			# AES CBC for the n-1 firsts block
			# First block if not the only one is decrypted and xored with IV (CBC)
			elif not plaintext:
				xored = ecb_cipher.decrypt(chunk)
				plaintext += bytes(xor(xored, iv))
				
			# Next blocks are decrypted and xored with the previous ciphertext (CBC)
			else:
				xored = ecb_cipher.decrypt(chunk)
				plaintext += bytes(xor(xored, ciphertext[pos-16:pos]))
				
		return plaintext
			
	
	def derive_password(self):
		salt = b"LESELFRANCAIS!!!"
		self.key = PBKDF2(self.password, salt, 16, count=10000, hmac_hash_module=SHA256)
