// SPDX-License-Identifier: MIT
pragma solidity ^0.8.26;

import {CasinoPWNME} from "./Casino.sol";

contract Setup {

    CasinoPWNME public casino;

    constructor() {
        casino = new CasinoPWNME();
    }

    function isSolved() public view returns (bool) {
        return casino.checkWin();
    }
    
}
