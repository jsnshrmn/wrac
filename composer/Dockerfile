FROM library/php:7.3-fpm

RUN apt update && apt install -y git unzip
#WORKDIR /app
RUN curl -sS https://getcomposer.org/installer | php && \
mkdir -p /opt/php/bin && \
mv composer.phar /opt/php/bin/composer
