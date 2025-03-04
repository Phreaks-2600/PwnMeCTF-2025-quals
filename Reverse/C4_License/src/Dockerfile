FROM ubuntu:22.04

WORKDIR /app

COPY app /app

RUN apt-get update -y
RUN apt-get install socat -y
RUN apt-get install python3-pip -y
RUN pip install --upgrade pip
RUN pip install --no-cache-dir -r requirements.txt

EXPOSE 1337

ENTRYPOINT ["socat", "TCP-LISTEN:1337,reuseaddr,fork", "EXEC:'python3 /app/checker.py'"]
