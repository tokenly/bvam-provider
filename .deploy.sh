#!/bin/bash

set -e

echo; echo "updating composer dependencies"
/usr/local/bin/composer.phar install --prefer-dist --no-progress

echo; echo "copying sample environment"
if [ ! -f .env ]; then cp -v .env.example .env; else echo ".env file already exists. Will not overwrite."; fi
