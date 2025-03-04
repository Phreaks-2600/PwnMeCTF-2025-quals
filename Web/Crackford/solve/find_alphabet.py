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