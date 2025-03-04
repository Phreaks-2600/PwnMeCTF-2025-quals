import re

alphabet = "Abcd3fgh1jkImn0pqrs7uvwxyz985462"

regex = re.compile(r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,7}\b')


def is_email(email):
    return re.fullmatch(regex, email)


def encode(input):
    bin_to_check = ""

    for i in input:
        bin_to_check += format(ord(i), "#010b")[2:]

    out = ""
    for chunk in [bin_to_check[i : i + 5] for i in range(0, len(bin_to_check), 5)]:
        out += alphabet[int(chunk, 2)]

    return out


def decode(input):
    formated = ""

    try:
        for i in input:
            formated += format(bin(alphabet.index(i)))[2:].rjust(5, "0")
    except:
        raise Exception("Impossible to decode input")

    out = ""
    for chunk in [formated[i : i + 8] for i in range(0, len(formated), 8)]:
        out += chr(int(chunk.ljust(8, "0"), 2))

    return out
