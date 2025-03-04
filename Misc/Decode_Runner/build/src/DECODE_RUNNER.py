import socket
import random
import threading
from time import sleep, time
import code_baudot
import code_unaire_chuck_norris
import charabia_latin
import chiffre_tritheme
import otan
import leet_speak
import shankar
import accord_de_guitare_cipher
import code_wabun
import morbit


# Liste des mots
# Liste de 100 mots possibles
words = [
    "abeille", "baleine", "camion", "danser", "elephant", "fromage", "grenouille",
    "hamster", "igloo", "jardin", "kangourou", "lampe", "magicien", "nager",
    "oiseau", "parapluie", "quiche", "robot", "serpent", "tigre", "uniforme",
    "vampire", "wagon", "xylophone", "yoga", "zebre", "avion", "brouette",
    "crocodile", "diamant", "ecureuil", "fleur", "guitare", "hippopotame",
    "insecte", "jambon", "klaxon", "loutre", "moustache", "navire", "ordinateur",
    "pizza", "quatre", "requin", "singe", "tomate", "ustensile", "vent",
    "walrus", "xylophone", "yeti", "zoo", "arc", "banane", "chien", "dauphin",
    "echelle", "fenetre", "gateau", "horloge", "iguanodon", "jasmin", "koala",
    "libellule", "maison", "noix", "orange", "panthere", "question", "raton",
    "souris", "tortue", "univers", "violon", "wagonnet", "xylocope", "yaourt",
    "zinc", "ananas", "bison", "clown", "dragon", "escargot", "fusil", "girafe",
    "huitre", "internet", "jouet", "kiwi", "loup", "moto", "ninja", "oiselet",
    "parc", "quartz", "riviere", "souris", "tuba", "usine", "vanille", "wagon"
]

words_guitar = ["decade", "deface", "degage", "badged", "baffed", "beaded", "bedded"]
words_wabun = ["apple", "cherry", "fig", "kiwi", "lemon","melon", "nectarine",
            "plum", "quince","watermelon", "apricot", "coconut", "currant","kumquat",
            "lime", "lychee","olive", "redcurrant", "starfruit", "tamarind", "ugli", "kiwano"]

FLAG = "PWNME{d4m_y0U_4r3_F4st!_4nd_y0u_kn0w_y0ur_c1ph3rs!}"


# Adresse et port du serveur
HOST = '0.0.0.0'
PORT = 12345

# Création du socket
server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server.bind((HOST, PORT))
server.listen(5)

banner = """

                               .:--==+++++++++==-:.
                           .-+*####################*+=:
                           :-+##########################+-.
                               :=*#########################=:
                                  .=*########################+:
                                 .:-=*#########################+:..
                              :=*###################################
                           .=*###################################*-=                       //    ) )
                           =####################################*+**.                     //    / /      ___        ___        ___        ___   /      ___
                           -==+*###############################%####*                    //    / /     //___) )   //   ) )   //   ) )   //   ) /     //___) )
                             .-=*####################################-                  //    / /     //         //         //   / /   //   / /     //
                          .-+########################################*                 //____/ /     ((____     ((____     ((___/ /   ((___/ /     ((____
                         .----=+##############################+--+*###
                       .......::-=*#*##########%#############+::::-#*+
                     .. ......::--==+########################-::::-**:
                      ..:::...:::-*##########################-:::-+*-
                     ...:--::..:=*#########################*+=::-=*-
                  ..:----=*+:...+**+*####################*-:---===:::.
           .:-===+*********+::.:=-- :-##*+*###########*=+------=-::=++                      //   ) )
        .:=====+*****+++++#*..:-......-*#############*:  ...::..                           //___/ /                  __         __        ___        __
      .-=*+=--==*##*****+*#+.-=:. .-:::.-##########*=:                                    / ___ (       //   / /   //   ) )   //   ) )   //___) )   //  ) )
     -+*###++==::****####**+-   ...-    =#####*++=:.                                     //   | |      //   / /   //   / /   //   / /   //         //
    :*######=::::-###+-..-:.=. .::-   .+###*###+.                                       //    | |     //   / /   //   / /   //   / /   //         //
    .+#######=---=+*:...:-.:=*+++=.  .*###: :####-                   ---               //     | |    ((___( (   //   / /   //   / /   ((____     //
       :---===::::::-::--.::+#######*###+.   .+###+.               :*#=#-
                    .-==-::-:-=+*#####*-       -####-             =#*#=**
                        ---.       ...          .*###+.          =****=**:
                                                  =####- .-    .-***#++**:
                                                   :*##*-+*-:....:-+*=+**.
                                                    .+--==-#+....:::-=***
                                                     .---:+%#+-::::-=*+*-
                                                    .---.-##****+---+*+=
                                                    .-:.-*#******=-+++=
                                                      .-+*******--+*+=
                                                         +##**+--+*+=
                                                          =##+--=**=
                                                           .---=*+-
                                                             :-+-.

"""

start = """\n
Welcome to Decode Runner ! You will receive 100 encoded words. Your goal is to decode them and send back the decoded word. You have 3 seconds to respond to each word. Good luck!

\n\n"""


def handle_client(client_socket):
    try :


        client_socket.send(banner.encode()+b'\n'+start.encode())
        sleep(0.2)

        for i in range(100):

            # Choix aléatoire de la méthode de chiffrement et du mot
            word = random.choice(words)
            method = random.choice(["chucknorris", "charabia_latin", "chiffre_de_tritheme", "baudot", "otan", "leet_speak", "shankar", "accord_de_guitare", "wabun", "morbit"])


            if method == "chucknorris":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: He can snap his toes, and has already counted to infinity twice ...\ncipher: " + code_unaire_chuck_norris.chuck_norris_encode(word) + "\n"
            elif method == "charabia_latin":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: what is this charabia ???\ncipher: " + charabia_latin.charabia_latin_encoder(word) + "\n"
            elif method == "chiffre_de_tritheme":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: Born in 1462 in Germany...\ncipher: " + chiffre_tritheme.chiffre_de_tritheme(word, 3) + "\n"
            elif method == "baudot":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: He can't imagine finding himself in CTF 150 years later...\ncipher: " + code_baudot.text_to_baudot(word) + "\n"
            elif method == "otan":
                print(f"Sending: {word} ({method})")
                encoded_word = "cipher: " + otan.encode_to_phonetic(word) + "\n"
            elif method == "accord_de_guitare":
                word = random.choice(words_guitar)
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: Hendrix would have had it... \ncipher: " + accord_de_guitare_cipher.word_to_chords(word) + "\n"
            elif method == "shankar":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: Did you realy see slumdog millionaire ?\ncipher: " + shankar.shankar_encrypt(word) + "\n"
            elif method == "wabun":
                word = random.choice(words_wabun)
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: It looks like Morse code, but ... \ncipher: " + code_wabun.encode_wabun(word) + "\n"
            elif method == "morbit":
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: A code based on pairs of dots and dashes. Think of a mix of Morse code and numbers... (AZERTYUIO)\ncipher: " + morbit.morbit_encoder(word) + "\n"
            elif method == "leet_speak":
                while 'i' in word or 'l' in word:
                    word = random.choice(words)
                print(f"Sending: {word} ({method})")
                encoded_word = "hint: 1337 ...\ncipher: " + leet_speak.leet_speak_encoder(word) + "\n"

            client_socket.send(encoded_word.encode())

            start_time = time()
            response = client_socket.recv(1024).decode().strip()
            elapsed_time = time() - start_time

            if elapsed_time > 3:
                client_socket.send(b'Too long sorry :) \n')
                client_socket.close()
                return

            if response != word:
                client_socket.send(b'Wrong answer sorry :) \n')
                client_socket.close()
                return

        win = f"Congratulations! You have solved the challenge.\nHere is your flag: {FLAG}\n"
        client_socket.send(win.encode())

    except Exception as e:
        print(f"Error handling client: {e}")
    finally:
        client_socket.close()  # Assurer que le socket est fermé

while True:
    client_socket, addr = server.accept()
    print(f"Connection from {addr}")
    thread = threading.Thread(target=handle_client, args=(client_socket,))
    thread.start()
