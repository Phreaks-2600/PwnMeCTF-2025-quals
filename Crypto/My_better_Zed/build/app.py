import os
import io
import string

from flask import Flask, render_template, send_file, request
from openzedlib import openzed



app = Flask(__name__, 
			template_folder="./web/templates",
			static_folder="./web/static")
			
FLAG = os.getenv("FLAG", "PWNME{flag_test}")
KEY = os.urandom(16)
USER = b"pwnme"

assert len(FLAG) <= 16

@app.route('/', methods=['GET'])
def home():
    return render_template('index.html')

@app.route('/encrypt_flag/', methods=['GET'])
def encrypt_flag():
	#chiffrer avec des creds random

	file = openzed.Openzed(USER, KEY, 'flag.txt.ozed')
	file.encrypt(FLAG.encode())
	file.generate_container()

	#tricking flask into thinking the data come from a file
	encrypted = io.BytesIO(file.secure_container)

	return send_file(
        encrypted,
        mimetype='text/plain',
        as_attachment=False,
        download_name='flag.txt.ozed'
    )


@app.route('/encrypt/', methods=['POST'])
def encrypt_file():
	
	if request.form["username"] and request.form["password"]:
		username = request.form["username"].encode()
		password = bytes.fromhex(request.form["password"])
	else:
		username = USER
		password = KEY

	if request.form["iv"] :
		try : 
			iv = request.form["iv"]
		except:
			return "Please submit iv hex encoded"
	else:
		iv = None

	if not request.files or not request.files["file"].filename:
		return "Please upload a file"
	

	filename = request.files["file"].filename
	file_to_encrypt = request.files['file']

	data = file_to_encrypt.stream.read()

	file = openzed.Openzed(username, password, filename, iv)
	file.encrypt(data)
	file.generate_container()

	encrypted = io.BytesIO(file.secure_container)

	return send_file(
        encrypted,
        mimetype='text/plain',
        as_attachment=False,
        download_name=filename+".ozed"
    )



@app.route('/decrypt/', methods=['POST'])
def decrypt_file():

	if not request.form["username"] :
		return "Please submit an username"

	if not request.form["password"]:
		return "Please submit a password"

	try : 
		bytes.fromhex(request.form["password"])
	except:
		return "Please submit the password hex encoded"

	if not request.files or not request.files["file"].filename:
		return "Please upload a file"
	
	username = request.form["username"].encode()
	password = bytes.fromhex(request.form["password"])

	filename = request.files["file"].filename
	file_to_decrypt = request.files['file']
	data = file_to_decrypt.stream.read()
		
	file = openzed.Openzed(username, password, filename)
	file.secure_container = data
	
	decrypted = file.decrypt_container(file.secure_container)
	decrypted = io.BytesIO(decrypted["data"])

	return send_file(
        decrypted,
        mimetype='text/plain',
        as_attachment=False,
        download_name=filename+".dec"
    )

# 10 MB = 2**20 * 10
if __name__ == "__main__":
	app.config['MAX_CONTENT_LENGTH'] = 1 * 1024 * 1024
	app.run(debug=False, host='0.0.0.0', port=5000)

