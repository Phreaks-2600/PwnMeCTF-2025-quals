def chuck_norris_encode(message):
    binary_message = ''.join(f'{ord(c):07b}' for c in message)  # Convertir chaque caractère en binaire de 7 bits
    encoded_message = []
    
    i = 0
    while i < len(binary_message):
        bit = binary_message[i]
        count = 1
        while i + 1 < len(binary_message) and binary_message[i + 1] == bit:
            count += 1
            i += 1
        if bit == '1':
            encoded_message.append('0 ' + '0' * count)
        else:
            encoded_message.append('00 ' + '0' * count)
        i += 1
    
    return ' '.join(encoded_message)