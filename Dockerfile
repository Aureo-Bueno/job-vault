FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && rm composer-setup.php

WORKDIR /var/www/html

# Step 1: Create logs directory
RUN mkdir -p /var/www/html/logs

# Step 2: Set permissions on logs
RUN chmod 777 /var/www/html/logs

# Step 3: Set ownership of entire project
RUN chown -R www-data:www-data /var/www/html

# Step 4: Set general permissions
RUN chmod -R 755 /var/www/html && chmod -R 777 /var/www/html/logs
