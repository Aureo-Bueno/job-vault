FROM php:8.2-apache

# System deps required by Composer to download/extract packages
RUN apt-get update \
  && apt-get install -y --no-install-recommends git unzip \
  && rm -rf /var/lib/apt/lists/*

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

# Composer (official image binary)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Install dependencies first to leverage layer cache
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

# Copy application source and generate the optimized autoloader
COPY . .
RUN composer dump-autoload --optimize \
  && mkdir -p logs \
  && chown -R www-data:www-data /var/www/html \
  && chmod -R 775 logs

# The entrypoint guarantees vendor/ and logs/ exist even when the project
# is bind-mounted over /var/www/html (docker-compose dev setup).
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
