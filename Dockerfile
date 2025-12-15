FROM php:8.2-apache

# Install SQLite dev headers then build pdo_sqlite
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY public/ /var/www/html/
COPY data/ /var/www/data/

# Ensure Apache can write to SQLite database location
RUN chown -R www-data:www-data /var/www/data

EXPOSE 80

CMD ["apache2-foreground"]

