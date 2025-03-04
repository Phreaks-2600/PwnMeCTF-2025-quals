from flask import Flask, render_template
from web3 import Web3
import yaml

rpc_url = "https://eth-sepolia.api.onfinality.io/public"
ca = "0x30c5394237B6064d417480E35C3130590b56a51D"

abi = [
	{
		"inputs": [
			{
				"internalType": "string",
				"name": "_value",
				"type": "string"
			}
		],
		"name": "manip_interrupt",
		"outputs": [],
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"inputs": [],
		"stateMutability": "nonpayable",
		"type": "constructor"
	},
	{
		"inputs": [],
		"name": "check",
		"outputs": [
			{
				"internalType": "string",
				"name": "",
				"type": "string"
			}
		],
		"stateMutability": "view",
		"type": "function"
	}
]



def get_contract_value(contract_address, contract_abi, rpc_url):
    # Connexion à Infura ou à un nœud Ethereum
    web3 = Web3(Web3.HTTPProvider(rpc_url))

    # Vérifiez si la connexion est réussie
    if not web3.is_connected():
        print("Erreur de connexion à Ethereum")
        return False

    contract = web3.eth.contract(address=contract_address, abi=contract_abi)

    try:
        value = contract.functions.check().call()
        if value == "pololo":
            return True
        else:
            return False
    except Exception as e:
        print(f"Erreur lors de l'appel à la fonction check: {e}")
        return False

app = Flask(__name__)

@app.route('/')
@app.route('/index.html')
def home():
    return render_template('index.html')

@app.route('/vip.html')
def vip():
    
    result = get_contract_value(ca, abi, rpc_url)
    
    if result:
        return render_template('vip.html')
    else:
        return render_template('index.html')

@app.route('/styles.css')
def styles():
    return render_template('styles.css')

if __name__ == '__main__':
    app.run(debug=False)
