#!/bin/sh
# Štart kontajnera: kľúč aplikácie, databáza, migrácie, voliteľne testovacie dáta, server.
set -e
cd /app

if [ -z "$APP_KEY" ]; then
    # Bez nastaveného APP_KEY sa vygeneruje dočasný kľúč (po reštarte sa odhlásia všetci používatelia).
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "Upozornenie: APP_KEY nie je nastavený – použitý dočasný kľúč."
fi

touch "$DB_DATABASE"
php artisan migrate --force

# SEED_DEMO=true → pri prázdnej databáze nahrá testovacie účty a dáta (august 2026).
if [ "$SEED_DEMO" = "true" ] && [ "$(php artisan tinker --execute 'echo App\Models\User::count();')" = "0" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}" --no-reload
