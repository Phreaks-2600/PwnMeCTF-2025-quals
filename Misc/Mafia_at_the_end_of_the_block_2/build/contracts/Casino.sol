// SPDX-License-Identifier: MIT
pragma solidity ^0.8.26;

contract CasinoPWNME {

  bool public isWinner;

	uint256 public multiplier = 14130161972673258133;
	uint256 public increment = 11367173177704995300;
	uint256 public modulus = 4701930664760306055;
  uint private state;

  constructor (){
    state = block.prevrandao % modulus;
  }

  function checkWin() public view returns (bool) {
    return isWinner;
  }

  function playCasino(uint number) public payable  {

    require(msg.value >= 0.1 ether, "My brother in christ, it's pay to lose not free to play !");
    PRNG();
    if (number == state){
      isWinner = true;
    } else {
      isWinner = false;
    }
  }
  
  function PRNG() private{
    state = (multiplier * state + increment) % modulus;
  }

}
  
