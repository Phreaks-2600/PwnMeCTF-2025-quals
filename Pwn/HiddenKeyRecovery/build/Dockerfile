from ubuntu:latest

run apt update -y && apt install -y socat python3 qemu-system

COPY ./chall /chall
COPY dma.py /dma.py
COPY ./run.sh /run.sh

CMD ["./run.sh"]
