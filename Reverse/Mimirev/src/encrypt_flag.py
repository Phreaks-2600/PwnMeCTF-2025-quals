import hashlib
from Crypto.Cipher import AES
import base64
from quopri import HEX

def pad(data):
    padding_length = 16 - (len(data) % 16)
    return data + bytes([padding_length] * padding_length)

def generate_encryption_key(entropy, bytecode_list):
    """
    Generate a 16-byte AES encryption key from the hash of the entropy and the hash of the bytecode.
    """
    # Convert the bytecode list to bytes
    bytecode = bytes(bytecode_list)

    # Hash the entropy
    hash_entropy = hashlib.sha256(str(entropy).encode()).hexdigest()

    # Hash the bytecode
    hash_bytecode = hashlib.sha256(bytecode).hexdigest()
    print(hash_bytecode, hash_entropy)
    # Combine the two hashes and hash again
    combined = (hash_entropy + hash_bytecode).encode()
    print(combined)
    final_key = hashlib.sha256(combined)
    print(final_key.hexdigest())
    # Ensure the key is 16 bytes (AES-128 requires 16-byte keys)
    print(final_key.digest()[:16])
    return final_key.digest()[:16]

def encrypt_string(data, key):
    """
    Encrypt the data using AES-128 ECB.
    """
    cipher = AES.new(key, AES.MODE_ECB)
    padded_data = pad(data.encode())

    encrypted = cipher.encrypt(padded_data)
    return base64.b64encode(encrypted).decode()

# Example usage
if __name__ == "__main__":
    # Inputs
    entropy = 959160445
    bytecode_list = [0, 1, 5, 0, 0, 1, 5, 1, 0, 5, 5, 2, 4, 2, 0, 0, 12, 3, 0, 0, 0, 29, 11, 3, 0, 0, 0, 39, 10, 0, 1, 5, 0, 3, 0, 0, 0, 83, 10, 4, 1, 4, 2, 17, 3, 0, 0, 0, 56, 11, 3, 0, 0, 0, 76, 10, 4, 0, 4, 1, 9, 5, 0, 4, 1, 0, 1, 6, 5, 1, 3, 0, 0, 0, 39, 10, 18, 3, 0, 0, 0, 83, 10, 18]
    string_to_encrypt = "PWNME{R3v3rS1ng_Compil0_C4n_B3_good}"

    # Generate the encryption key
    encryption_key = generate_encryption_key(entropy, bytecode_list)
    print(f"Encryption Key: {encryption_key.hex()}")

    # Encrypt the string
    encrypted_string = encrypt_string(string_to_encrypt, encryption_key)
    print(f"Encrypted String: {encrypted_string}")
