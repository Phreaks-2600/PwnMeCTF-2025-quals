# Dictionnaire de l'alphabet phonétique de l'OTAN
phonetic_alphabet = {
    'A': 'Alfa', 'B': 'Bravo', 'C': 'Charlie', 'D': 'Delta',
    'E': 'Echo', 'F': 'Foxtrot', 'G': 'Golf', 'H': 'Hotel',
    'I': 'India', 'J': 'Juliett', 'K': 'Kilo', 'L': 'Lima',
    'M': 'Mike', 'N': 'November', 'O': 'Oscar', 'P': 'Papa',
    'Q': 'Quebec', 'R': 'Romeo', 'S': 'Sierra', 'T': 'Tango',
    'U': 'Uniform', 'V': 'Victor', 'W': 'Whiskey', 'X': 'X-ray',
    'Y': 'Yankee', 'Z': 'Zulu', '0': 'Zero', '1': 'One',
    '2': 'Two', '3': 'Three', '4': 'Four', '5': 'Five',
    '6': 'Six', '7': 'Seven', '8': 'Eight', '9': 'Nine'
}

def encode_to_phonetic(text):
    # Transformer le texte en majuscules pour correspondre aux clés du dictionnaire
    text = text.upper()
    encoded_text = []
    
    for char in text:
        if char in phonetic_alphabet:
            encoded_text.append(phonetic_alphabet[char])
        else:
            encoded_text.append(char)  # Garder les caractères non-alphanumériques tels quels
    
    return ' '.join(encoded_text)