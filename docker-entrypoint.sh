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

# Railway sets PORT env var; make Apache listen on it
# Falls back to 80 if PORT is not set (local dev)
sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

# Hand off to Apache
exec apache2-foreground
