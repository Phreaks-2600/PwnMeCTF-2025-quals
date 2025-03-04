docker compose up -d
ET
docker compose up -d --build 

pour tout rebuild si des fichiers changent

Définir dans le docker-compose.yml :

ENV=prodcution
changer PUBLIC_IP
changer le shared_secret qui est "redactedredacted à l'origine"


et changer le reste si besoin


Il est possible de changer le nombre de worker et threads dans `src/98-start-gunicorn`
