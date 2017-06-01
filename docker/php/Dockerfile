#
# NOTE: THIS DOCKERFILE IS GENERATED VIA "update.sh"
#
# PLEASE DO NOT EDIT IT DIRECTLY.
#

#
# NOTE: THIS DOCKERFILE IS GENERATED VIA "update.sh"
#
# PLEASE DO NOT EDIT IT DIRECTLY.
#

FROM php:7.1.5-cli

# Install tools
COPY sources.list /etc/apt/sources.list
RUN apt-get update
RUN apt-get install gdb -y
RUN apt-get install vim -y
RUN apt-get install zip -y
RUN apt-get install git -y

# Install hiredis
COPY hiredis.tar.gz /home
RUN cd /home \
    && tar -zxf hiredis.tar.gz \
    && cd hiredis \
    && make && make install && ldconfig \
    && rm -rf /home/hiredis*

# Install Swoole
COPY swoole-src-2.0.7.tar.gz /home
RUN cd /home \
    && tar -zxf swoole-src-2.0.7.tar.gz \
    && cd /home/swoole-src-2.0.7 \
    && phpize && ./configure --enable-coroutine --enable-async-redis --enable-debug && make && make install \
    && docker-php-ext-enable swoole \
    && rm -rf /home/swoole-src-2.0.7.tar.gz

# Install Xdebug
COPY xdebug-2.5.4.tar.gz /home
RUN cd /home \
    && tar -zxf xdebug-2.5.4.tar.gz \
    && cd /home/xdebug-2.5.4 \
    && phpize && ./configure && make && make install \
    && rm -rf /home/xdebug* \
    && echo "alias phpunit='php -dzend_extension=xdebug.so /var/www/vendor/bin/phpunit'" >> ~/.bashrc

# composer
COPY composer.phar /usr/bin/composer
RUN composer config -g repo.packagist composer https://packagist.phpcomposer.com

#Install other extensions
RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www

CMD composer install && ulimit -c unlimited && ./hong --env=.env-docker swoole:serve
