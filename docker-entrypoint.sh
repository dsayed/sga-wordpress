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

# Ensure only mpm_prefork is loaded (required for mod_php)
# Railway's container runtime can re-enable mpm_event; force prefork at startup
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ 2>/dev/null || true
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ 2>/dev/null || true

# Auto-setup WordPress on first boot (idempotent — skips if already installed)
if command -v wp &>/dev/null && [ -n "$WP_HOME" ]; then
  WP="wp --allow-root --path=/var/www/html/web/wp"
  if ! $WP core is-installed 2>/dev/null; then
    echo "=== First boot: installing WordPress ==="
    $WP core install \
      --url="$WP_HOME" \
      --title="Saving Great Animals" \
      --admin_user="admin" \
      --admin_password="${WP_ADMIN_PASSWORD:-changeme}" \
      --admin_email="admin@savinggreatanimals.org"

    $WP theme activate sga
    $WP plugin activate the-events-calendar

    $WP option update blogdescription "The Right Dog For The Right Home"
    $WP option update timezone_string "America/Los_Angeles"
    $WP option update date_format "F j, Y"
    $WP option update permalink_structure "/%postname%/"
    $WP option update show_on_front "posts"

    # Create pages
    $WP post delete 1 --force 2>/dev/null || true
    $WP post delete 2 --force 2>/dev/null || true
    $WP post create --post_type=page --post_title='Adopt' --post_name='adopt' --post_status=publish --post_content='[available_dogs]'
    $WP post create --post_type=page --post_title='Foster' --post_name='foster' --post_status=publish
    $WP post create --post_type=page --post_title='Dogs Needing Fosters' --post_name='dogs-needing-fosters' --post_status=publish --post_content='[foster_dogs]'
    $WP post create --post_type=page --post_title='Get Involved' --post_name='get-involved' --post_status=publish
    $WP post create --post_type=page --post_title='About' --post_name='about' --post_status=publish
    $WP post create --post_type=page --post_title='Donate' --post_name='donate' --post_status=publish
    $WP post create --post_type=page --post_title='Surrender' --post_name='surrender' --post_status=publish
    $WP post create --post_type=page --post_title='Resources' --post_name='resources' --post_status=publish
    $WP post create --post_type=page --post_title='Events' --post_name='events' --post_status=publish

    # Populate page content
    if [ -f /var/www/html/scripts/populate-content.php ]; then
      echo "Populating page content..."
      cat /var/www/html/scripts/populate-content.php | $WP eval-file -
    fi

    # Import seed images BEFORE creating foster dogs so attachment slugs
    # don't collide (fosters and images share names like "binky", "aiden")
    if [ -d /tmp/seed-uploads ] && [ -n "$(ls /tmp/seed-uploads/ 2>/dev/null)" ]; then
      echo "=== Importing seed images ==="
      mkdir -p /var/www/html/web/app/uploads/seed
      cp -rn /tmp/seed-uploads/* /var/www/html/web/app/uploads/seed/
      for img in /var/www/html/web/app/uploads/seed/*; do
        [ -f "$img" ] || continue
        ATTACH_ID=$($WP media import "$img" --porcelain 2>/dev/null) || true
        if [ "$(basename "$img")" = "sgalogo-1.png" ] && [ -n "$ATTACH_ID" ]; then
          $WP option update site_logo "$ATTACH_ID"
          $WP option update site_icon "$ATTACH_ID"
        fi
      done
      echo "=== Seed images imported ==="
    fi

    # Populate foster dogs (after images so photo lookups succeed)
    if [ -f /var/www/html/scripts/populate-fosters.sh ]; then
      echo "Populating foster dogs..."
      /bin/sh /var/www/html/scripts/populate-fosters.sh
    fi

    # Create editor accounts
    $WP user create lily lily@savinggreatanimals.org --role=editor --display_name='Lily Piecora' 2>/dev/null || true
    $WP user create jacintha jacintha@savinggreatanimals.org --role=editor --display_name='Jacintha Sayed' 2>/dev/null || true

    echo "=== WordPress setup complete ==="
  fi

  # Sync seed images into the uploads volume (runs on every boot, not just first)
  # On Railway, the uploads volume persists but starts empty. Seed images are
  # stashed at /tmp/seed-uploads during Docker build (before the volume mount).
  # Skips if images are already imported (checks for any attachment posts).
  if [ -d /tmp/seed-uploads ] && [ -n "$(ls /tmp/seed-uploads/ 2>/dev/null)" ]; then
    ATTACHMENT_COUNT=$($WP post list --post_type=attachment --format=count 2>/dev/null) || ATTACHMENT_COUNT=0
    if [ "$ATTACHMENT_COUNT" -eq 0 ]; then
      echo "=== Importing seed images ==="
      mkdir -p /var/www/html/web/app/uploads/seed
      cp -rn /tmp/seed-uploads/* /var/www/html/web/app/uploads/seed/
      for img in /var/www/html/web/app/uploads/seed/*; do
        [ -f "$img" ] || continue
        ATTACH_ID=$($WP media import "$img" --porcelain 2>/dev/null) || true
        if [ "$(basename "$img")" = "sgalogo-1.png" ] && [ -n "$ATTACH_ID" ]; then
          $WP option update site_logo "$ATTACH_ID"
          $WP option update site_icon "$ATTACH_ID"
        fi
      done
      echo "=== Seed images imported ==="
    fi
  fi
fi

# Hand off to Apache
exec apache2-foreground
