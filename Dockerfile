FROM php:8.2-apache

# Install system dependencies for Composer and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd \
    && rm -rf /var/lib/apt/lists/*

# Ensure only mpm_prefork is loaded (required for mod_php)
# and enable mod_rewrite for WordPress permalinks
RUN a2dismod mpm_event 2>/dev/null; a2enmod mpm_prefork rewrite

# Set the document root to Bedrock's web/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer for dependency management
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install WP-CLI for automated WordPress setup
RUN curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

WORKDIR /var/www/html

# Copy project files and install dependencies
# Local dev uses volume mounts instead, so COPY only runs in standalone builds (Railway)
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stash seed images so the entrypoint can copy them into the uploads volume
# (Railway's volume mount at web/app/uploads/ hides the image layer's files)
RUN cp -r web/app/uploads/seed /tmp/seed-uploads 2>/dev/null || true

# Entrypoint generates .htaccess and configures Apache PORT at runtime
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

CMD ["docker-entrypoint.sh"]
