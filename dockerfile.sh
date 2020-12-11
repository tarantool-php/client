#!/usr/bin/env bash

if [[ -z "$PHP_IMAGE" ]]; then
    PHP_IMAGE='php:7.4-cli'
fi

if [[ -z "$TNT_PACKER" ]]; then
    TNT_PACKER='pure'
fi

if [[ -z "$TNT_LISTEN_URI" ]]; then
    TNT_LISTEN_URI='tarantool:3301'
fi

RUN_CMDS=''
if [[ -n "$COVERAGE_FILE" ]]; then
    RUN_CMDS="$RUN_CMDS && \\\\\n    pecl install pcov && docker-php-ext-enable pcov"
fi

if [[ -z "$EXT_DISABLE_DECIMAL" || "0" == "$EXT_DISABLE_DECIMAL" || "false" == "$EXT_DISABLE_DECIMAL" ]] ; then
  RUN_CMDS="$RUN_CMDS && \\\\\n    apt-get install -y libmpdec-dev"
  RUN_CMDS="$RUN_CMDS && \\\\\n    pecl install decimal && docker-php-ext-enable decimal"
fi

COMPOSER_REMOVE=''
if [[ "$PHP_IMAGE" =~ 7.1 ]]; then
  COMPOSER_REMOVE='symfony/uid'
fi

echo -e "
FROM $PHP_IMAGE

RUN apt-get update && \\
    apt-get install -y curl git uuid-dev unzip && \\
    docker-php-ext-install sockets && \\
    pecl install msgpack && docker-php-ext-enable msgpack && \\
    pecl install uuid && docker-php-ext-enable uuid${RUN_CMDS}

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV PATH=~/.composer/vendor/bin:\$PATH
ENV TNT_PACKER=$TNT_PACKER TNT_LISTEN_URI=$TNT_LISTEN_URI

CMD if [ ! -f composer.lock ]; then ${COMPOSER_REMOVE:+composer remove --dev --no-update }$COMPOSER_REMOVE${COMPOSER_REMOVE:+ && }composer install; fi && \\
    vendor/bin/phpunit ${COVERAGE_FILE:+ --coverage-text --coverage-clover=}$COVERAGE_FILE
"
