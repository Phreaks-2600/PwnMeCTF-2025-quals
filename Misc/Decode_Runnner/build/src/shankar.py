# Alphabets définis selon la substitution de Shankar
original_alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
shankar_alphabet = "XWYAZBCDQEFGHIKLMNOPJRSTUV"

def shankar_encrypt(text):

    """Chiffre un texte en utilisant l'alphabet de Shankar."""
    encrypt_dict = {original_alphabet[i]: shankar_alphabet[i] for i in range(len(original_alphabet))}
    encrypted_text = ""
    for char in text.upper():
        if char in encrypt_dict:
            encrypted_text += encrypt_dict[char]
        else:
            encrypted_text += char  # Pour conserver les caractères non alphabétiques
    return encrypted_text
