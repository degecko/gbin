#!/usr/bin/env bash

run() {
    docker compose run --rm $@
}

exec() {
    docker exec -it $@
}

rebuild() {
    docker compose up -d --build $@
}

restart() {
    docker restart -t 5 $@
}

if [ "$(hostname)" == "ALPACA" ]; then IS_DEV=true; fi

LAST_TAG=$(git describe --tags $(git rev-list --tags --max-count=1))

sed -i "s/APP_VERSION=.*/APP_VERSION=$LAST_TAG/g" .env &&
    run composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction &&
    run npm i &&
    run npm run prod &&
    run -w /var/www/nova-components/YcoHelpers npm i --no-dev &&
    run -w /var/www/nova-components/YcoHelpers npm run prod &&
    rebuild --build php &&
    docker compose -f docker-compose.yml $([ -n "$IS_DEV" ] && echo "-f docker-compose.dev.yml") up -d --build mentech &&
    restart octane &&
    run artisan horizon:terminate &&
    run artisan migrate --force &&
    sleep 2 &&
    restart php && exec php kill -USR2 1 &&
    restart mentech && exec mentech nginx -s reload &&
    run php php artisan cache:clear &&
    run php php artisan config:cache &&
    run php php artisan route:cache &&
    run php php artisan view:cache &&
    sleep 2 &&
    exec php kill -USR2 1

cp server/nginx/proxy.conf /etc/nginx/sites-enabled/dev.menager-technic.fr

nginx -s reload
