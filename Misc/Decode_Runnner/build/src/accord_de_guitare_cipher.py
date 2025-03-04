# Dictionnaire de correspondance des lettres aux accords de guitare
chords_dict = {
    'A': 'x02220',
    'B': 'x24442',
    'C': 'x32010',
    'D': 'xx0232',
    'E': '022100',
    'F': '133211',
    'G': '320003'
}

def word_to_chords(word):
    # Convertir chaque lettre du mot en accord
    chords = [chords_dict[letter.upper()] for letter in word if letter.upper() in chords_dict]
    # Joindre les accords avec des espaces
    return ' '.join(chords)