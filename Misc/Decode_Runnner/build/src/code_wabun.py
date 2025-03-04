# Dictionnaire complet du Code Wabun pour les syllabes japonaises
alphabet = "a, i, u, e, o, ka, ki, ku, ke, ko, sa, shi, su, se, so, ta, chi, tsu, te, to, na, ni, nu, ne, no, ha, hi, fu, he, ho, ma, mi, mu, me, mo, ya, yu, yo, ra, ri, ru, re, ro, wa, wi, n, we, wo, kya, kyu, kyo, sha, shu, sho, cha, chu, cho, nya, nyu, nyo, hya, hyu, hyo, mya, myu, myo, rya, ryu, ryo, ga, gi, gu, ge, go, gya, gyu, gyo, za, ji, zu, ze, zo, ja, ju, jo, da, ji, zu, de, do, ja, ju, jo, ba, bi, bu, be, bo, bya, byu, byo, pa, pi, pu, pe, po, pya, pyu, pyo"
alphabet_wabun = alphabet.split(", ")

code = "--.--   .-   ..-   -.---   .-...   .-..   -.-..   ...-   -.--   ----   -.-.-   --.-.   ---.-   .---.   ---.   -.   ..-.   .--.   .-.--   ..-..   .-.   -.-.   ....   --.-   ..--   -...   --..-   --..   .   -..   -..-   ..-.-   -   -...-   -..-.   .--   -..--   --   ...   --.   -.--.   ---   .-.-   -.-   .-..-   .-.-.   .--..   .---   -.-.. .--   -.-.. -..--   -.-.. --   --.-. .--   --.-. -..--   --.-. --   ..-. .--   ..-. -..--   ..-. --   -.-. .--   -.-. -..--   -.-. --   --..- .--   --..- -..--   --..- --   ..-.- .--   ..-.- -..--   ..-.- --   --. .--   --. -..--   --. --   .-.. ..   -.-.. ..   ...- ..   -.-- ..   ---- ..   -.-.. .. .--   -.-.. .. -..--   -.-.. .. --   -.-.- ..   ..-. ..   .--. ..   .---. ..   ---. ..   ..-. .. .--   ..-. .. -..--   ..-. .. --   -. ..   ..-. ..   .--. ..   .-.-- ..   ..-.. ..   ..-. .. .--   ..-. .. -..--   ..-. .. --   -... ..   --..- ..   --.. ..   . ..   -.. ..   --..- .. .--   --..- .. -..--   --..- .. --   -... ..--.   --..- ..--.   --.. ..--.   . ..--.   -.. ..--.   --..- ..--. .--   --..- ..--. -..--   P --"
code_wabun = code.split("   ")

wabun_code = dict(zip(alphabet_wabun, code_wabun))

def encode_wabun(text):
    encoded_text = []
    i = 0
    while i < len(text):
        # Check for multi-character syllables first
        if i + 3 <= len(text) and text[i:i+3] in wabun_code:
            encoded_text.append(wabun_code[text[i:i+3]])
            i += 3
        elif i + 2 <= len(text) and text[i:i+2] in wabun_code:
            encoded_text.append(wabun_code[text[i:i+2]])
            i += 2
        elif i + 1 <= len(text) and text[i:i+1] in wabun_code:
            encoded_text.append(wabun_code[text[i:i+1]])
            i += 1
        else:
            encoded_text.append(text[i])  # caractère non pris en charge
            i += 1
    return ' '.join(encoded_text)