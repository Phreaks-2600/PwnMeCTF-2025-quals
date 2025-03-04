FROM alpine:3.16.0

ARG DEBIAN_FRONTEND=noninteractive
WORKDIR /app/

RUN apk add socat python3 py-pip

RUN python3 -m pip install pycryptodome

COPY ./* /app/

RUN addgroup -S ctf && \
    adduser -S player -G ctf && \
    chmod u+s /app/server.py


ARG FLAG
RUN echo "FLAG='PWNME{K3ls3y_aNd_5cHn3ieR_1s_4w3s0m3}'" >> /app/secret.py

RUN chown player:ctf /app/challenge.py
USER player

ENTRYPOINT ["socat","TCP-LISTEN:3000,reuseaddr,fork","EXEC:'python3 /app/server.py',stderr"]
