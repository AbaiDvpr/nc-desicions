FROM composer:2.2.4 as composer

FROM alpine:3.16

WORKDIR /srv/www

ENV PHP_INI_DIR /etc/php81

ENV PHPIZE_DEPS \
        autoconf \
        dpkg-dev dpkg \
        file \
        g++ \
        gcc \
        libc-dev \
        make \
        pkgconf \
        re2c

ARG uname=www-data
ARG gid=33
ARG uid=33

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
RUN apk --no-cache add \
    nginx supervisor \
    git ca-certificates bzip2 icu-libs libavif curl pcre-dev pcre2-dev rabbitmq-c rabbitmq-c-dev \
    $PHPIZE_DEPS \
    php81 \
    php81-dev \
    php81-common \
    php81-gd \
    php81-xmlreader \
    php81-xmlwriter \
    php81-tokenizer \
    php81-bcmath \
    php81-ctype \
    php81-curl \
    php81-fileinfo \
    php81-exif \
    php81-iconv \
    php81-intl \
    php81-pdo \
    php81-pdo_mysql \
    php81-posix \
    php81-mbstring \
    php81-pecl-redis \
    php81-opcache \
    php81-openssl \
    php81-pcntl \
    php81-phar \
    php81-soap \
    php81-session \
    php81-simplexml \
    php81-xml \
    php81-xsl \
    php81-zip \
    php81-zlib \
    php81-dom \
    php81-fpm \
    php81-sockets \
    php81-pecl-amqp \
    php81-pecl-igbinary \
    php81-pdo_sqlite \
    php81-pecl-mongodb \
    # Iconv Fix
    php81-pecl-apcu

RUN rm -rf /srv/www/* && \
    rm -rf /var/cache/apk/* && \
    apk del php81-dev musl-dev m4 pcre-dev pcre2-dev rabbitmq-c-dev $PHPIZE_DEPS

RUN deluser xfs && \
    adduser -u ${uid} -D -S -G ${uname} ${uname} && \
    mkdir /etc/sudoers.d && \
    echo "${uname} ALL=(ALL) NOPASSWD: /bin/sed, /bin/echo, /bin/cp" > /etc/sudoers.d/${uname} && \
    chmod 0440 /etc/sudoers.d/${uname} && \
    mkdir -p /home/$uname/.composer && \
    chown -R $uname:$uname /home/$uname

RUN ln -s php81 /usr/bin/php && \
    ln -s /usr/sbin/php-fpm81 /usr/bin/php-fpm


EXPOSE 80

CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
