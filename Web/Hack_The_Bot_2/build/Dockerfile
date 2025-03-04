FROM node:18-slim

RUN apt-get update && apt-get install -y \
    gconf-service libasound2 libatk1.0-0 libcairo2 libcups2 libfontconfig1 libgdk-pixbuf2.0-0 \
    libgtk-3-0 libnspr4 libpango-1.0-0 libxss1 fonts-liberation libappindicator1 libnss3 \
    lsb-release xdg-utils wget nginx


RUN wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb && \
    dpkg -i google-chrome-stable_current_amd64.deb; apt-get -fy install && \
    rm google-chrome-stable_current_amd64.deb

RUN mkdir -p /app /tmp/bot_folder/logs /tmp/bot_folder/browser_cache

COPY /source /app
COPY flag2.txt /root/
COPY nginx.conf /etc/nginx/nginx.conf

WORKDIR /app

RUN npm install

EXPOSE 80

CMD service nginx start && node app.js
