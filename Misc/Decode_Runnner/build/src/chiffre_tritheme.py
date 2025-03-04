def chiffre_de_tritheme(texte, decalage):
    resultat = []
    for i, char in enumerate(texte):
        if char.isalpha():
            # Calculer le décalage pour chaque caractère
            decalage_actuel = (decalage + i) % 26
            if char.islower():
                # Calculer le nouveau caractère pour les minuscules
                nouveau_char = chr((ord(char) - ord('a') + decalage_actuel) % 26 + ord('a'))
            else:
                # Calculer le nouveau caractère pour les majuscules
                nouveau_char = chr((ord(char) - ord('A') + decalage_actuel) % 26 + ord('A'))
            resultat.append(nouveau_char)
        else:
            resultat.append(char)  # Conserver les caractères non alphabétiques
    return ''.join(resultat)