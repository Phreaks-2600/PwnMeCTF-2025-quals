FROM ubuntu:24.04

WORKDIR /app/

RUN apt-get update && apt-get install socat -y

COPY ./compresse /app/run
COPY ./flag /app/flag

RUN groupadd --system ctf && \
	useradd -M -d /app -g ctf player && \
	chmod +r /app/flag && \
	chmod +x /app/run

USER player

ENTRYPOINT ["socat", "TCP-LISTEN:1337,reuseaddr,fork", "EXEC:'/app/run',stderr"]
