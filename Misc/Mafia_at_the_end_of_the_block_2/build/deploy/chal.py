from pathlib import Path

import eth_sandbox
import eth_sandbox.launcher
from eth_typing import HexStr
from web3 import Web3
from web3.types import Wei
import json


def deploy(web3: Web3, deployer_address: str, player_address: str) -> str:


    receipt_transfer = eth_sandbox.launcher.send_transaction(
        web3,
        {
            "from": deployer_address,
            "to": player_address,
            "value": web3.to_wei(1, "ether"), #1 ether
        },
    )
    assert receipt_transfer is not None


    receipt = eth_sandbox.launcher.send_transaction(
        web3,
        {
            "from": deployer_address,
            "data": json.loads(Path("/home/ctf/compiled/Setup.sol/Setup.json").read_text())["bytecode"]["object"],
            "value": Wei(0),
        },
    )

    print(receipt)
    assert receipt is not None

    setup_address = receipt["contractAddress"]
    assert setup_address is not None

    setup_contract = web3.eth.contract(address=setup_address, abi=json.loads(Path("/home/ctf/compiled/Setup.sol/Setup.json").read_text())["abi"])
    challenge_address = setup_contract.functions.casino().call()   
    assert challenge_address is not None
    
    return setup_address, challenge_address

eth_sandbox.launcher.run_launcher(
    [
        eth_sandbox.launcher.new_launch_instance_action(deploy),
        eth_sandbox.launcher.new_kill_instance_action(),
        eth_sandbox.launcher.new_get_flag_action(),
    ]
)
