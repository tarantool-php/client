#!/usr/bin/env python

import os
import re


image = os.getenv('IMAGE', 'php:5.6-cli')
client = os.getenv('TNT_CLIENT', 'pure')
packer = os.getenv('TNT_PACKER', 'pecl')
phpunit_opts = os.getenv('PHPUNIT_OPTS', '')

run_cmds = []
composer_cmds = []

if image.startswith('php:'):
    run_cmds.append('apt-get install -y zlib1g-dev && docker-php-ext-install zip')
    run_cmds.append('docker-php-ext-install sockets')

    if packer.startswith('pecl'):
        if image.startswith('php:7'):
            msgpack_ext_version='php7'
        else:
            msgpack_ext_version='master'
        run_cmds.append('git clone https://github.com/msgpack/msgpack-php.git {0} && git --git-dir={0}/.git --work-tree={0} checkout {1}'.format('/usr/src/php/ext/msgpack', msgpack_ext_version))
        run_cmds.append('docker-php-ext-install msgpack')
        composer_cmds.append('remove --dev rybakit/msgpack')
    else:
        composer_cmds.append('remove --dev ext-msgpack')

    if 'pecl' == client:
        run_cmds.append('git clone https://github.com/tarantool/tarantool-php.git /usr/src/php/ext/tarantool')
        run_cmds.append('docker-php-ext-install tarantool')
else:
    composer_cmds.append('remove --dev ext-msgpack')


if re.match('(?:^|\s+?)--coverage-\w', phpunit_opts):
    run_cmds.append('pecl install xdebug && docker-php-ext-enable xdebug')

run_cmds = " && \\\n    ".join(run_cmds)
if run_cmds:
    run_cmds = "\nRUN " + run_cmds + "\n"

composer_cmds = ' && composer '.join(composer_cmds)
if composer_cmds:
    composer_cmds = 'composer ' + composer_cmds + ' && '

print '''
FROM {image}

RUN apt-get update && apt-get install -y git curl
{run_cmds}
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \\
    composer global require 'phpunit/phpunit:^4.8|^5.0'

ENV PATH=~/.composer/vendor/bin:$PATH
ENV TNT_CONN_HOST=tarantool TNT_CONN_PORT=3301
ENV TNT_CLIENT={client} TNT_PACKER={packer}

CMD if [ ! -f composer.lock ]; then {composer_cmds}composer install; fi && ~/.composer/vendor/bin/phpunit {phpunit_opts}
'''.format(
    image=image,
    run_cmds=run_cmds,
    composer_cmds=composer_cmds,
    client=client,
    packer=packer,
    phpunit_opts=phpunit_opts
)
