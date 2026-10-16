FROM php:8.3.30-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libxml2-dev libonig-dev unzip git curl python3 mariadb-client \
    && docker-php-ext-install intl pdo_mysql opcache dom xml mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.9.5 /usr/bin/composer /usr/local/bin/composer
RUN git config --system --add safe.directory /app
COPY .docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /app/backend
COPY .docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint
ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
