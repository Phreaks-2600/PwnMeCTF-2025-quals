FROM alpine:3.19

WORKDIR /usr/app
COPY ./src/ /usr/app/

RUN apk add --update --no-cache \
    nodejs~=20                  \
    firefox                     \
    npm~=10                  && \
    npm install

CMD ["/usr/bin/node", "/usr/app/app.js"]
