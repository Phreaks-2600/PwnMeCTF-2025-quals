# TL;DR

There are 3 vulnerabilities.

- the hash function in python has a collision for -1 and -2
- with the previous vuln the nonce is biased by 7 bits (which are constant but unknown)
- we can forge an ECDSA signature from an already known one (we can forge a new pair (r,s) by subtracting the value of s from the order of the curve, which gives us (r,s')) and so we can get 60 signatures to break the scheme

Finally we just need to apply section 4.3 of the following paper to exploit the challenge: https://eprint.iacr.org/2019/023.pdf