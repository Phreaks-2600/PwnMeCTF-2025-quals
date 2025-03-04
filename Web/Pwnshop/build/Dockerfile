FROM php:8.0-apache

RUN echo "coucou3"

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libgd-dev \
    libjpeg-dev \
    libpng-dev \
    default-mysql-server \
    default-mysql-client \
    gcc \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd

RUN cd /usr/lib/x86_64-linux-gnu/gconv/gconv-modules.d \
    && cat gconv-modules-extra.conf | grep -v -E 'CN-?EXT' > gconv-modules-extra-patched.conf \
    && mv gconv-modules-extra-patched.conf gconv-modules-extra.conf \
    && rm -f ../gconv-modules.cache \
    && iconvconfig \
    && iconv -l | grep -E 'CN-?EXT' && exit 1 || true   

COPY custom-php.ini /usr/local/etc/php/conf.d/custom.ini

COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

COPY app/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/ 

COPY ./flag.txt /root/
COPY ./getflag.c /root/
RUN gcc -o /getflag /root/getflag.c && \
    chmod u+s /getflag && \
    rm /root/getflag.c

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY init.sql /docker-entrypoint-initdb.d/init.sql

RUN mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld
RUN mkdir -p /var/lib/mysql && chown -R mysql:mysql /var/lib/mysql
RUN chmod 777 /var/run/mysqld

EXPOSE 80

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
