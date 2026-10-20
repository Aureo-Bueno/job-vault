#!/bin/sh
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

# Owner of the project files (host user when bind-mounted, www-data inside the image)
OWNER="$(stat -c '%u:%g' composer.json)"

# When the project is bind-mounted, vendor/ comes from the host and may be
# missing or incomplete. `composer install` is idempotent: it only downloads
# what is missing and regenerates the autoloader.
if [ -f composer.lock ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
  chown -R "$OWNER" vendor
fi

# Logs directory (hidden by the bind mount, so create it at runtime)
mkdir -p logs
chown "$OWNER" logs
chmod 777 logs

exec docker-php-entrypoint "$@"
