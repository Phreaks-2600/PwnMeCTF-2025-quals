# Table de conversion pour les lettres (Lettres)
baudot_letters = {
    'A': '00011', 'B': '11001', 'C': '01110', 'D': '01001', 'E': '00001', 'F': '01101',
    'G': '11010', 'H': '10100', 'I': '00110', 'J': '01011', 'K': '01111', 'L': '10010',
    'M': '11100', 'N': '01100', 'O': '11000', 'P': '10110', 'Q': '10111', 'R': '01010',
    'S': '00101', 'T': '10000', 'U': '00111', 'V': '11110', 'W': '10011', 'X': '11101',
    'Y': '10101', 'Z': '10001', ' ': '00100'
}

# Table de conversion pour les chiffres et les symboles (Figures)
baudot_figures = {
    '1': '00011', '2': '11001', '3': '01110', '4': '01001', '5': '00001', '6': '01101',
    '7': '11010', '8': '10100', '9': '00110', '0': '01011', '-': '01111', '!': '10010',
    '&': '11100', '#': '01100', "'": '11000', '(': '10110', ')': '10111', '"': '01010',
    '/': '00101', ':': '10000', ';': '00111', '?': '11110', ',': '10011', '.': '11101',
    '+': '10101', '=': '10001', ' ': '00100'
}

# Fonction pour convertir une chaîne de caractères en code Baudot
def text_to_baudot(text):
    mode = 'letters'  # Mode initial: Lettres
    result = []

    for char in text.upper():
        if char in baudot_letters:
            if mode == 'figures':
                result.append('11111')  # Passer en mode Lettres
                mode = 'letters'
            result.append(baudot_letters[char])
        elif char in baudot_figures:
            if mode == 'letters':
                result.append('11011')  # Passer en mode Figures
                mode = 'figures'
            result.append(baudot_figures[char])
        else:
            raise ValueError(f"Caractère non supporté: {char}")

    return ' '.join(result)