from openzedlib.aes_cbc_zed import AES_CBC_ZED 
from hashlib import sha256

import json
import zlib
import os


class Openzed:

	def __init__(
		self, 
		user : bytes = b"user", 
		password : bytes = b"OpenZEDdefaultpasswordtochangebeforedeployinproduction", 
		filename : str = "file.txt", 
		iv : str = None
	) :
		self.user = user
		self.password = password
		self.filename = filename
		self.generate_iv(iv)
		self.generate_metadata()

	"""Metadata 
	
	format : {"filename": "", "user": "", "password_hash": "", "iv": ""}+padding

	(metadata size = 283 bytes maximum (rounded up to 300) and formatted in json)
	
	header ("OZED") -> 4
	filename -> 64
	user -> 64
	password_hash -> 64
	iv -> 32
	"""

	def generate_metadata(self):
		
		metadata = {}
		metadata["user"] = self.user.decode()
		metadata["password_hash"] = sha256(self.password).hexdigest()
		metadata["filename"] = self.filename

		if self.filename.endswith(".ozed"):
			self.filename = self.filename[:-5]
		
		metadata["iv"] = self.iv.hex()

		self.metadata = json.dumps(metadata).encode()
		self.padding_len = 300-len(self.metadata)
		self.metadata += self.padding_len*b"\x00"	

		return self.metadata
	
	def encrypt(self, data):
	
		cipher = AES_CBC_ZED(self.user, self.password, self.iv)
		self.encrypted = cipher.encrypt(data)
		self.encrypted = zlib.compress(self.encrypted) # just for the lore	

		return self.encrypted

	def decrypt(self, ciphertext):

		cipher = AES_CBC_ZED(self.user, self.password, self.iv)
		ciphertext = zlib.decompress(ciphertext)
		self.decrypted = cipher.decrypt(ciphertext)
		
		return self.decrypted

	def generate_container(self):
		self.secure_container = b'OZED' + self.metadata + self.encrypted
		return self.secure_container

	def decrypt_container(self, container):
	
		if not container.startswith(b"OZED"):
			raise ValueError("Not a OZED file")
			
		self.read_metadata()

		filename = self.parsed_metadata["filename"]
		self.iv = bytes.fromhex(self.parsed_metadata["iv"])
		ciphertext = container[304:]
		plaintext = self.decrypt(ciphertext)

		return {"data":plaintext, "filename":filename}

	def read_metadata(self):
		
		self.parsed_metadata = json.loads(self.secure_container[4:300-self.padding_len+4])
		return self.parsed_metadata	


	def generate_iv(self, iv):
		if iv == None:
			self.iv = os.urandom(16)
			return
		
		try:

			iv = bytes.fromhex(iv)
			if len(iv) != 16:
				raise ValueError("IV should have a length of 16")
			self.iv = iv

		except Exception as e:
			raise e