FROM composer:latest

COPY cf-dynamic-ip.php composer.json composer.lock /cf-dynamic-ip/

RUN cd /cf-dynamic-ip && composer install

CMD php /cf-dynamic-ip/cf-dynamic-ip.php
