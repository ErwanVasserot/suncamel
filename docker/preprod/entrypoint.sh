#!/bin/sh

set -eu

if [ "$#" -gt 0 ] && [ "$1" = "apache2-foreground" ]; then
    echo "Applying SunCamel database migrations..."
    php bin/console doctrine:migrations:migrate \
        --no-interaction \
        --allow-no-migration \
        --env=prod \
        --no-debug

    echo "Refreshing SunCamel production cache..."
    php bin/console cache:clear \
        --env=prod \
        --no-debug

    mkdir -p var public/images/suncamel/products
    chown -R www-data:www-data var public/images/suncamel/products
fi

exec "$@"
