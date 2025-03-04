numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9]
bigram = ["..", ".-", "./", "-.", "--", "-/", "/.", "/-", "//"]
key_sorted = sorted("AZERTYUIO")
index_numeric = dict(zip(key_sorted, numbers))

key = "AZERTYUIO"
ordered_values = [index_numeric[letter] for letter in key]

# Associate each letter with its corresponding bigram
bigram_dict = dict(zip(ordered_values, bigram))

MORSE_CODE_DICT = {
    'A': '.-', 'B': '-...', 'C': '-.-.', 'D': '-..', 'E': '.', 'F': '..-.', 'G': '--.', 'H': '....',
    'I': '..', 'J': '.---', 'K': '-.-', 'L': '.-..', 'M': '--', 'N': '-.', 'O': '---', 'P': '.--.',
    'Q': '--.-', 'R': '.-.', 'S': '...', 'T': '-', 'U': '..-', 'V': '...-', 'W': '.--', 'X': '-..-',
    'Y': '-.--', 'Z': '--..', '1': '.----', '2': '..---', '3': '...--', '4': '....-', '5': '.....',
    '6': '-....', '7': '--...', '8': '---..', '9': '----.', '0': '-----', ', ': '--..--', '.': '.-.-.-',
    '?': '..--..', '/': '-..-.', '-': '-....-', '(': '-.--.', ')': '-.--.-', '&': '.-...',
    ':': '---...', ';': '-.-.-.', '=': '-...-', '+': '.-.-.', '_': '..--.-', '"': '.-..-.',
    '$': '...-..-', '!': '-.-.--', '@': '.--.-.', ' ': ' '
}

def chiffrement_morse(message):
    message = message.upper()
    morse_code = ''
    for letter in message:
        if letter in MORSE_CODE_DICT:
            morse_code += MORSE_CODE_DICT[letter] + '/'
        elif letter == ' ':
            morse_code = morse_code.rstrip('/') + '//'
        else:
            morse_code += '?/'  # For unsupported characters
    # if len(morse_code) // 2 != 0 -> add a / at the end
    if len(morse_code) % 2 != 0:
        morse_code = morse_code.rstrip('/') + '/'
    return morse_code

def decode_morse(morse_code):
    morse_code = morse_code.split(' ')
    decoded_message = ''
    for code in morse_code:
        for key, value in MORSE_CODE_DICT.items():
            if code == value:
                decoded_message += key
    return decoded_message


def morbit_encoder(word):
    morse_word = chiffrement_morse(word).replace(" ", "")
    split_word = [morse_word[i:i+2] for i in range(0, len(morse_word), 2)]

    # Create a reverse dictionary for decoding bigrams
    reverse_bigram_dict = {v: k for k, v in bigram_dict.items()}

    # Check and replace bigram with the corresponding number
    encoded_word = []
    for bigram in split_word:
        if bigram in reverse_bigram_dict:
            encoded_word.append(str(reverse_bigram_dict[bigram]))
        else:
            continue
    return ''.join(encoded_word)