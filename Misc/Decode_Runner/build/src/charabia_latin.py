import random

def charabia_latin_encoder(word):
    # Inverser le mot
    reversed_word = word[::-1]
    # Choisir une terminaison aléatoire
    ending = random.choice(['us', 'um', 'it'])
    # Ajouter la terminaison à la fin
    encoded_word = reversed_word + ending
    return encoded_word