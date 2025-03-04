FROM ubuntu:22.04

ARG DEBIAN_FRONTEND=noninteractive
WORKDIR /app/

RUN apt-get update && apt-get install -y socat python3 python3-pip

RUN python3 -m pip install --upgrade pip
RUN pip3 install -v pycryptodome tinyec

COPY ./server.py /app/

RUN groupadd -r ctf && \
    useradd -r -g ctf player && \
    chmod u+s /app/server.py

RUN echo "FLAG=\"PWNME{weird_things_leads_t0_weird_shit}\"" > /app/flag.py

RUN chown player:ctf /app/server.py
USER player

ENTRYPOINT ["socat","TCP-LISTEN:3000,reuseaddr,fork","EXEC:'python3.10 /app/server.py',stderr"]
