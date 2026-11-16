# Image de démonstration en ligne (Render) : PHP 8.4 + Apache, assets compilés pendant la construction.
# Voir docs/DEMO-EN-LIGNE.md.

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-apache

# Valeurs par défaut de la démonstration (remplacées par celles saisies dans l'hébergeur, le cas échéant).
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_LOCALE=fr \
    APP_FALLBACK_LOCALE=fr \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    TRUSTED_PROXIES=* \
    DB_CONNECTION=mysql \
    SESSION_DRIVER=database \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    SMS_DRIVER=log \
    DEMO_RESERVATIONS=true

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd intl zip bcmath exif opcache \
    && a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-uam.ini

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover \
    && chown -R www-data:www-data storage bootstrap/cache

COPY --chmod=0755 docker/start.sh /usr/local/bin/demarrer
CMD ["demarrer"]
