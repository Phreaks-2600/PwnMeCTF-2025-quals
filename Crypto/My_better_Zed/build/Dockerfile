FROM alpine:3.16.0


COPY requirements.txt /app/

WORKDIR /app/

RUN apk --update add python3 py-pip \
	&& python3 -m pip install -r requirements.txt

RUN addgroup -S ctf && \
    adduser -S player -G ctf 

COPY app.py /app/
COPY openzedlib/ /app/openzedlib/
COPY web/ /app/web/

RUN chown player:ctf -R /app/

USER player

ENV FLAG="PWNME{zEd_15_3z}"
EXPOSE 5000
ENTRYPOINT ["python3", "/app/app.py"]
