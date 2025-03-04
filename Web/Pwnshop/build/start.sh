#!/bin/bash

mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld
chmod 777 /var/run/mysqld

if [ ! -d "/var/lib/mysql/${DB_DATABASE}" ]; then
    echo "Initialisation de MySQL..."
    mysqld --initialize-insecure --user=mysql
    mysqld --skip-networking &
    sleep 5

    mysql -u root -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE};"
    mysql -u root -e "CREATE USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';"
    mysql -u root -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE}.* TO '${DB_USERNAME}'@'%';"
    mysql -u root -e "FLUSH PRIVILEGES;"
    mysql -u root "${DB_DATABASE}" < /docker-entrypoint-initdb.d/init.sql
    killall mysqld
    sleep 2
fi

mysqld_safe --bind-address=127.0.0.1 &

sleep 5

if ! mysqladmin ping -h "127.0.0.1" --silent; then
    exit 1
fi

exec apache2-foreground
