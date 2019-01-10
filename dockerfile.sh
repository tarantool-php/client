#!/usr/bin/env bash

if [[ -z "$IMAGE" ]]; then
    IMAGE='php:7.3-cli'
fi

if [[ -z "$TNT_PACKER" ]]; then
    TNT_PACKER='pure'
fi

if [[ -z "$TNT_CONN_URI" ]]; then
    TNT_CONN_URI='tcp://127.0.0.1:3301'
fi

if [[ $TNT_PACKER == pecl ]]; then
    RUN_CMDS="$RUN_CMDS && \\\\\n    pecl install msgpack && docker-php-ext-enable msgpack"
    COMPOSER_REMOVE='rybakit/msgpack'
else
    COMPOSER_REMOVE='ext-msgpack'
fi

if [[ "1" != "$CHECK_CS" ]]; then
    COMPOSER_REMOVE="$COMPOSER_REMOVE friendsofphp/php-cs-fixer"
fi

echo -e "
FROM $IMAGE

RUN apt-get update && \\
    apt-get install -y git curl libzip-dev && \\
    docker-php-ext-configure zip --with-libzip && \\
    docker-php-ext-install zip${RUN_CMDS}

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV PATH=~/.composer/vendor/bin:\$PATH
ENV TNT_PACKER=$TNT_PACKER TNT_CONN_URI=$TNT_CONN_URI

CMD if [ ! -f composer.lock ]; then ${COMPOSER_REMOVE:+composer remove --dev --no-update }$COMPOSER_REMOVE${COMPOSER_REMOVE:+ && }composer install; fi && \\
    vendor/bin/phpunit${COVERAGE_FILE:+ --coverage-text --coverage-clover=}$COVERAGE_FILE
"
