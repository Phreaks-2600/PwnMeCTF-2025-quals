FROM pwn.red/jail
COPY --from=ubuntu:23.10 / /srv

COPY ./flag /srv/flag
COPY ./einstein /srv/app/run
