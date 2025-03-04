leet_dict = {
        'A': '4', 'B': '8', 'C': 'C', 'D': 'D', 'E': '3', 'F': 'F', 'G': '6', 'H': 'H', 'I': '1', 'J': 'J',
        'K': '|<', 'L': '1', 'M': 'M', 'N': 'N', 'O': '0', 'P': 'P', 'Q': 'Q', 'R': 'r', 'S': '5', 'T': '7',
        'U': 'U', 'V': 'V', 'W': 'W', 'X': 'X', 'Y': 'Y', 'Z': 'Z'
    }


def leet_speak_encoder(text):
    
    # Convert the text to uppercase to match the leet dictionary keys
    text = text.upper()

    # Encode the text into leet speak
    encoded_text = ''
    for char in text:
        if char in leet_dict:
            encoded_text += leet_dict[char]
        else:
            encoded_text += char

    return encoded_text