FROM debian:latest

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /app/

RUN apt-get update --fix-missing \
    && apt-get install -qy --no-install-recommends socat=1.7.* \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/ \
    && useradd -d /home/ctf -m ctf -s /bin/bash \
    && chown ctf:ctf /usr/bin/socat \
    && chmod 500 /usr/bin/socat

COPY ./flag.txt ./
COPY ./snapshot_blob.bin ./
COPY ./d8 ./

USER ctf

EXPOSE 4000

CMD ["/usr/bin/socat", "tcp-listen:4000,reuseaddr,fork", "exec:'timeout -k 121 120 stdbuf -o0 /app/d8'"]
