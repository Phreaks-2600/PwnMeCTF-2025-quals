#!/usr/bin/env python3

import subprocess
from time import sleep
import re

MAPS_LINE_RE = re.compile(r"""
    (?P<addr_start>[0-9a-f]+)-(?P<addr_end>[0-9a-f]+)\s+  
    (?P<perms>\S+)\s+                                     
    (?P<offset>[0-9a-f]+)\s+                              
    (?P<dev>\S+)\s+                                       
    (?P<inode>\d+)\s+                                     
    (?P<pathname>.*)\s+                                   
""", re.VERBOSE)

def get_int(message):
    while True:
        try:
            result = int(input(message + "\n> "))
            return result
        except Exception as e:
            print(f"Error : {e}")

def handle_read(fd, wher):
    fd.seek(phys_start + wher)
    return(fd.read(8))

def handle_write(fd, wher):
    wat = get_int("What ?")
    fd.seek(phys_start + wher)
    fd.write(wat.to_bytes(8, 'little'))
    print(f"[+] Wrote {hex(wat)} at address {hex(wher)}")


pid = subprocess.run(['pidof', 'qemu-system-x86_64'], stdout=subprocess.PIPE).stdout.decode('utf-8').strip()
if pid == "":
    print("Didn't find a qemu instance running, please contact an admin if you want to submit your exploit, otherwise try locally first.")
    exit(0)

with open(f"/proc/{pid}/maps") as f:
    for i in f:
        value = MAPS_LINE_RE.match(i).groups()
        if (int(value[1], 16)-int(value[0], 16)) == 0x100000000:
            phys_start = int(value[0], 16)
            break

allowed_dma_range = range(0x7e4ed000, 0x7e6ecfff+1) 

fd = open(f"/proc/{pid}/mem", "rb+")

print("-" * 10 + " Welcome To Super DMA " + "-" * 10 + "\n")

while True:
    mode = get_int("Would like to read (0) or write (1)?")
    wher = get_int("Where ?")
    if wher in allowed_dma_range:
        handle_write(fd, wher) if mode else handle_read(fd, wher)
    else:
        print("Take the allowed range as a hint, not a restriction :)")

fd.close()
