def get_used_chars(input):
    c_unique = set()
    for c in input:
        c_unique.add(c)
    l = list(c_unique)
    l.sort()
    return l

chaine = "mfrggzdfmz7wq9Iknnwg987p0byx358u0v8h06dzp1ydcmr7gq97mnzyhfp7s0bxgy971mzsg3yhu6Iy048hk4d70jyxA880nvwgw97jnb7wmzI3mnrgcx9b1jbu1rkg1433sssIjrgu579qkfjfgvcvkzIvqwk91bwwc9Imfz7h32brgy8hyucxjzguk1cdkrdA"
print("".join(get_used_chars(chaine)))