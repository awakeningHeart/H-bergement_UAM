#!/bin/sh
# Démarrage du conteneur de démonstration : base à jour, données de démonstration, planificateur, puis Apache.
set -e
cd /var/www/html

# Sans clé valide, toutes les pages avec session échouent (erreur 500) : on s'arrête avec un message clair.
case "$APP_KEY" in
    base64:*) ;;
    *) echo "ERREUR : APP_KEY absente ou invalide. Valeur attendue : « base64:… » (php artisan key:generate --show)." >&2; exit 1 ;;
esac
if ! php -r 'exit(strlen((string) base64_decode(substr(getenv("APP_KEY"), 7), true)) === 32 ? 0 : 1);'; then
    echo "ERREUR : APP_KEY mal recopiée (la partie après « base64: » doit décoder 32 octets)." >&2
    exit 1
fi

# Render impose le port d'écoute via $PORT.
PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Certificat de l'autorité de la base MySQL (connexion chiffrée), fourni en variable d'environnement.
if [ -n "$DB_CA_CERT" ]; then
    printf '%s\n' "$DB_CA_CERT" > /var/www/html/storage/ca-bdd.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/ca-bdd.pem
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force
php artisan db:seed --force
if [ "$DEMO_RESERVATIONS" = "true" ]; then
    php artisan db:seed --class=DemoReservationsSeeder --force
fi

php artisan storage:link || true
php artisan filament:assets
chown -R www-data:www-data storage bootstrap/cache public/css public/js public/fonts 2>/dev/null || true

# Tâches planifiées (expiration des demandes, libération des chambres bloquées…).
su www-data -s /bin/sh -c "php artisan schedule:work" &

exec apache2-foreground
