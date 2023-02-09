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

rebuild --build codeg-php &&
  rebuild codeg &&
  restart octane &&
  run artisan horizon:terminate &&
  run artisan migrate --force &&
  sleep 2 &&
  restart codeg-php && exec codeg-php kill -USR2 1 &&
  restart codeg && exec codeg nginx -s reload &&
  run php codeg-php artisan cache:clear &&
  run php codeg-php artisan config:cache &&
  run php codeg-php artisan route:cache &&
  run php codeg-php artisan view:cache &&
  sleep 2 &&
  exec php kill -USR2 1

cp server/nginx/proxy.conf /etc/nginx/sites-enabled/code.gecko.dev

nginx -s reload
