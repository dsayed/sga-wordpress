FROM php:8.2-apache

# Install system dependencies for Composer and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for WordPress permalinks
RUN a2enmod rewrite

# Set the document root to Bedrock's web/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer for dependency management
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Copy project files and install dependencies
# Local dev uses volume mounts instead, so COPY only runs in standalone builds (Railway)
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Entrypoint generates .htaccess and configures Apache PORT at runtime
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

CMD ["docker-entrypoint.sh"]
