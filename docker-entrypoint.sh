#!/usr/bin/env bash
set -e

# Generate .htaccess for WordPress pretty permalinks
# This must run at container start because the webroot is not persistent
# on Railway (only the uploads volume persists across deploys)
cat > /var/www/html/web/.htaccess << 'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS

# Configure Apache to listen on the correct port
# Railway sets PORT env var; falls back to 80 for local dev
LISTEN_PORT="${PORT:-80}"
echo "Listen ${LISTEN_PORT}" > /etc/apache2/ports.conf
cat > /etc/apache2/sites-available/000-default.conf << VHOST
<VirtualHost *:${LISTEN_PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot ${APACHE_DOCUMENT_ROOT:-/var/www/html/web}
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
    <Directory ${APACHE_DOCUMENT_ROOT:-/var/www/html/web}>
        AllowOverride All
    </Directory>
</VirtualHost>
VHOST

# Hand off to Apache
exec apache2-foreground
