#!/usr/bin/env python

import os
import re


image = os.getenv('IMAGE', 'php:5.6-cli')
client = os.getenv('TNT_CLIENT', 'pure')
packer = os.getenv('TNT_PACKER', 'pecl')
conn_uri = os.getenv('TNT_CONN_URI', 'tcp://tarantool:3301')
coverage_file = os.getenv('COVERAGE_FILE', '')

run_cmds = []
composer_cmds = []

phpunit_opts = ''
phpunit_exclude_groups = []

if image.startswith('php:'):
    run_cmds.append('apt-get install -y zlib1g-dev && docker-php-ext-install zip')

    if 'pecl' == client:
        run_cmds.append('git clone https://github.com/tarantool/tarantool-php.git /usr/src/php/ext/tarantool')
        run_cmds.append('echo tarantool >> /usr/src/php-available-exts && docker-php-ext-install tarantool')
        composer_cmds.append('remove --dev rybakit/msgpack')
        phpunit_opts += ' --testsuite Integration'
        phpunit_exclude_groups.append('pure_only')
        packer = ''

    if packer.startswith('pecl'):
        if image.startswith('php:7'):
            msgpack_ext_version='master'
        else:
            msgpack_ext_version='php5'
        run_cmds.append('git clone https://github.com/msgpack/msgpack-php.git {0} && git --git-dir={0}/.git --work-tree={0} checkout {1}'.format('/usr/src/php/ext/msgpack', msgpack_ext_version))
        run_cmds.append('echo msgpack >> /usr/src/php-available-exts && docker-php-ext-install msgpack')
        composer_cmds.append('remove --dev rybakit/msgpack')
    else:
        composer_cmds.append('remove --dev ext-msgpack')
else:
    composer_cmds.append('remove --dev ext-msgpack')


if coverage_file:
    phpunit_opts += ' --coverage-clover ' + coverage_file
    run_cmds.append('pecl install xdebug && docker-php-ext-enable xdebug')

run_cmds = " && \\\n    ".join(run_cmds)
if run_cmds:
    run_cmds = "\nRUN " + run_cmds + "\n"

composer_cmds = ' && composer '.join(composer_cmds)
if composer_cmds:
    composer_cmds = 'composer ' + composer_cmds + ' && '

if conn_uri.startswith('tcp:'):
    phpunit_exclude_groups.append('unix_only')
else:
    phpunit_exclude_groups.append('tcp_only')

phpunit_exclude_groups = ','.join(phpunit_exclude_groups)
if phpunit_exclude_groups:
    phpunit_opts += ' --exclude-group ' + phpunit_exclude_groups

print '''
FROM {image}

RUN apt-get update && apt-get install -y git curl
{run_cmds}
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \\
    composer global require 'phpunit/phpunit:^4.8|^5.0'

ENV PATH=~/.composer/vendor/bin:$PATH
ENV TNT_CLIENT={client} TNT_PACKER={packer} TNT_CONN_URI={conn_uri}

CMD if [ ! -f composer.lock ]; then {composer_cmds}composer install; fi && ~/.composer/vendor/bin/phpunit{phpunit_opts}
'''.format(
    image=image,
    run_cmds=run_cmds,
    composer_cmds=composer_cmds,
    client=client,
    packer=packer,
    conn_uri=conn_uri,
    phpunit_opts=phpunit_opts
)
