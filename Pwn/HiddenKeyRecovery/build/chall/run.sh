#!/bin/sh

exec qemu-system-x86_64 \
    -m 4096M \
    -smp 2 \
    -nographic \
    -machine q35,smm=on,accel=tcg \
    -nic user,model=virtio-net-pci \
    -global ICH9-LPC.disable_s3=1 \
    -global driver=cfi.pflash01,property=secure,value=on \
    -drive if=pflash,format=raw,file=/chall/bios.bin \
    -kernel /chall/bzImage \
    -append "console=ttyS0 oops=panic panic=-1 pti=on ignore_loglevel" \
    -no-reboot \
    -initrd /chall/initramfs.cpio.gz \
    -monitor none \
    -vga none \
    -display none \
    

