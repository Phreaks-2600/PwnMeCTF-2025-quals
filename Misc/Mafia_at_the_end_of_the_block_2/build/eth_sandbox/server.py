import json
import os
import random
import signal
import subprocess
import time
from threading import Thread
from uuid import uuid4

import requests
import rlp
from Crypto.Random.random import randint
from eth_account import Account
from eth_account.hdaccount import generate_mnemonic
from eth_account.signers.local import LocalAccount
from eth_sandbox.auth import get_shared_secret
from eth_sandbox.launcher import send_transaction
from eth_typing import HexStr
from flask import Flask, Request, Response, request, render_template, redirect, url_for
from flask_cors import CORS, cross_origin
from web3 import Web3
from web3.types import TxReceipt
from pathlib import Path

app = Flask(__name__,
    template_folder="/tmp/web/templates",
    static_folder="/tmp/web/static")

CORS(app)

ENV = os.getenv("ENV", "production")
HTTP_PORT = os.getenv("HTTP_PORT", "8545")
FLAG = os.getenv("FLAG", "PWNME{test_flag}")


try:
    os.mkdir("/tmp/instances-by-uuid")
except Exception:
    pass


def has_instance_by_uuid(uuid: str) -> bool:
    return os.path.exists(f"/tmp/instances-by-uuid/{uuid}")


def get_instance_by_uuid(uuid: str) -> dict:
    with open(f"/tmp/instances-by-uuid/{uuid}", "r") as f:
        return json.loads(f.read())


def delete_instance_info(node_info: dict) -> None:
    os.remove(f'/tmp/instances-by-uuid/{node_info["uuid"]}')


def create_instance_info(node_info: dict) -> None:
    with open(f'/tmp/instances-by-uuid/{node_info["uuid"]}', "w+") as f:
        f.write(json.dumps(node_info))


def really_kill_node(node_info: dict) -> None:
    print(f"killing node {node_info['uuid']}")

    delete_instance_info(node_info)

    os.kill(node_info["pid"], signal.SIGTERM)


def kill_node(node_info: dict) -> None:
    time.sleep(60 * 60 * 1)

    if not has_instance_by_uuid(node_info["uuid"]):
        return

    really_kill_node(node_info)


def launch_node() -> dict | None:
    port = random.randrange(30000, 60000)
    mnemonic = generate_mnemonic(12, "english")
    mnemonic_user = generate_mnemonic(12, "english")
    uuid = str(uuid4())

    anvil_command = [
        "/root/.foundry/bin/anvil",
        "--accounts",
        "2",
        "--balance",
        "100",
        "--mnemonic",
        mnemonic,
        "--port",
        str(port),
        "--block-base-fee-per-gas",
        "0",
        "--hardfork",
        "cancun",
    ]

    proc = subprocess.Popen(args=anvil_command)

    web3 = Web3(Web3.HTTPProvider(f"http://127.0.0.1:{port}"))
    while True:
        if proc.poll() is not None:
            return None
        if web3.is_connected():
            break
        time.sleep(0.1)

    node_info = {
        "port": port,
        "mnemonic": mnemonic,
        "mnemonic_user": mnemonic_user,
        "pid": proc.pid,
        "uuid": uuid,
    }

    reaper = Thread(target=kill_node, args=(node_info,))
    reaper.start()
    return node_info

def is_request_authenticated(request: Request) -> bool:
    token = request.headers.get("Authorization")

    return token == f"Bearer {get_shared_secret()}"

def get_casino_win_value(uuid):

    node_info = get_instance_by_uuid(uuid)
    abi = json.loads(Path("/home/ctf/compiled/Setup.sol/Setup.json").read_text())["abi"]
    setup_contract = json.loads(Path(f"/tmp/{uuid}").read_text())["address"]
    rpc = f"http://127.0.0.1:{node_info['port']}"

    web3 = Web3(Web3.HTTPProvider(rpc))

    if not web3.is_connected():
        print("Erreur de connexion au noeud ethereum")
        return False

    contract = web3.eth.contract(address=setup_contract, abi=abi)

    try:
        value = contract.functions.isSolved().call()
        if value:
            return True
        else:
            return False
    except Exception as e:
        print(f"Erreur lors de l'appel à la fonction check: {e}")
        return False


@app.route("/")
def index() -> str:
    return "sandbox is running!"

@app.route("/pow", methods=["GET"])
def pow() -> str:
    return Path("/home/ctf/solve-pow.py").read_text()

@app.route("/new", methods=["POST"])
@cross_origin()
def create() -> dict:
    if not is_request_authenticated(request):
        return {
            "ok": False,
            "error": "nice try", 
        }

    print("launching node")

    node_info = launch_node()
    if node_info is None:
        print("failed to launch node")
        return {
            "ok": False,
            "error": "error_starting_chain",
            "message": "An error occurred while starting the chain",
        }
    create_instance_info(node_info)

    print(f"launched node (uuid={node_info['uuid']}, pid={node_info['pid']})")

    return {
        "ok": True,
        "uuid": node_info["uuid"],
        "mnemonic": node_info["mnemonic"],
        "mnemonic_user": node_info["mnemonic_user"],
    }


@app.route("/kill", methods=["POST"])
@cross_origin()
def kill() -> dict:
    if not is_request_authenticated(request):
        return {
            "ok": False,
            "error": "nice try",
        }

    body = request.get_json()

    uuid = body["uuid"]

    if not has_instance_by_uuid(uuid):
        print(f"no instance to kill for uuid {uuid}")
        return {
            "ok": False,
            "error": "not_running",
            "message": "No instance is running!",
        }

    really_kill_node(get_instance_by_uuid(uuid))

    return {
        "ok": True,
        "message": "Instance killed",
    }

ALLOWED_NAMESPACES = ["web3", "eth", "net"]
DISALLOWED_METHODS = [
    "eth_sign",
    "eth_signTransaction",
    "eth_signTypedData",
    "eth_signTypedData_v3",
    "eth_signTypedData_v4",
    "eth_sendTransaction",
    "eth_sendUnsignedTransaction",
]


@app.route("/<string:uuid>", methods=["POST"])
@cross_origin()
def proxy(uuid: str) -> str | dict | Response:
    body = request.get_json()
    if not body:
        return "invalid content type, only application/json is supported"

    if "id" not in body:
        return ""

    if not has_instance_by_uuid(uuid):
        return {
            "jsonrpc": "2.0",
            "id": body["id"],
            "error": {
                "code": -32602,
                "message": "invalid uuid specified",
            },
        }

    node_info = get_instance_by_uuid(uuid)

    if "method" not in body or not isinstance(body["method"], str):
        return {
            "jsonrpc": "2.0",
            "id": body["id"],
            "error": {
                "code": -32600,
                "message": "invalid request",
            },
        }

    ok = (
        any(body["method"].startswith(namespace) for namespace in ALLOWED_NAMESPACES)
        and body["method"] not in DISALLOWED_METHODS
    )
    if not ok and not is_request_authenticated(request):
        return {
            "jsonrpc": "2.0",
            "id": body["id"],
            "error": {
                "code": -32600,
                "message": "forbidden jsonrpc method",
            },
        }

    resp = requests.post(f"http://127.0.0.1:{node_info['port']}", json=body)
    response = Response(resp.content, resp.status_code, resp.raw.headers.items())
    return response



###### Interaction with the WEB interface ######

@app.route("/<string:uuid>/", methods=["GET"])
@app.route("/<string:uuid>/index.html", methods=["GET"])
def home(uuid):
    

    if not has_instance_by_uuid(uuid):
        return "invalid uuid specified"

    else:
        node_info = get_instance_by_uuid(uuid)
        casino_abi = json.loads(Path("/home/ctf/compiled/Casino.sol/CasinoPWNME.json").read_text())["abi"]
        casino_address = json.loads(Path(f"/tmp/{uuid}").read_text())["target_address"]
        rpc = f"http://127.0.0.1:{HTTP_PORT}/{uuid}"

        return render_template('index.html', casino_abi=casino_abi, casino_address=casino_address, rpc=rpc)


@app.route("/<string:uuid>/vip.html", methods=["GET"])
def vip(uuid):
    

    if not has_instance_by_uuid(uuid):
        return "Invalid uuid specified"

    value = get_casino_win_value(uuid)

    if value == True:
        return render_template('vip.html', flag=FLAG)
    else:
        return redirect(url_for("home", uuid=uuid))





if __name__ == "__main__":
    app.run(host="0.0.0.0", port=int(HTTP_PORT))
