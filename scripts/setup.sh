#!/usr/bin/env bash
set -euo pipefail

# SGA WordPress Setup Script
# Bootstraps a fresh WordPress install with the correct theme, plugins, and settings.
# Usage: docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/setup.sh

WP="wp --allow-root --path=/var/www/html/web/wp"

echo "=== SGA WordPress Setup ==="

# Wait for database
# Docker's depends_on:service_healthy handles MySQL readiness. This short
# sleep covers the gap between MySQL accepting connections and WordPress
# being fully able to use them (DNS resolution, connection pool warmup).
echo "Waiting for database..."
sleep 5
echo "Database ready."

# Install WordPress if not already installed
if ! $WP core is-installed 2>/dev/null; then
  echo "Installing WordPress..."
  $WP core install \
    --url="http://localhost:8080" \
    --title="Saving Great Animals" \
    --admin_user="admin" \
    --admin_password="admin" \
    --admin_email="admin@example.com"
  echo "WordPress installed."
else
  echo "WordPress already installed, skipping."
fi

# Activate theme
echo "Activating theme..."
$WP theme activate sga

# Activate plugins
echo "Activating plugins..."
$WP plugin activate the-events-calendar

# Set basic options
echo "Configuring site options..."
$WP option update blogdescription "The Right Dog For The Right Home"
$WP option update timezone_string "America/Los_Angeles"
$WP option update date_format "F j, Y"
$WP option update permalink_structure "/%postname%/"

# Generate .htaccess for pretty permalinks
# WP-CLI's rewrite flush can't write .htaccess from the CLI container,
# so we write it directly. This is the standard WordPress rewrite block.
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
echo ".htaccess created."

# Remove default content
echo "Cleaning default content..."
$WP post delete 1 --force 2>/dev/null || true
$WP post delete 2 --force 2>/dev/null || true
$WP comment delete 1 --force 2>/dev/null || true

# Create main pages
echo "Creating pages..."
$WP post create --post_type=page --post_title='Adopt' --post_name='adopt' --post_status=publish --post_content='[available_dogs]'
$WP post create --post_type=page --post_title='Foster' --post_name='foster' --post_status=publish
$WP post create --post_type=page --post_title='Dogs Needing Fosters' --post_name='dogs-needing-fosters' --post_status=publish --post_content='[foster_dogs]'
$WP post create --post_type=page --post_title='Get Involved' --post_name='get-involved' --post_status=publish
$WP post create --post_type=page --post_title='About' --post_name='about' --post_status=publish
$WP post create --post_type=page --post_title='Donate' --post_name='donate' --post_status=publish
$WP post create --post_type=page --post_title='Surrender' --post_name='surrender' --post_status=publish
$WP post create --post_type=page --post_title='Resources' --post_name='resources' --post_status=publish
$WP post create --post_type=page --post_title='Events' --post_name='events' --post_status=publish

# Set homepage to use front-page.html template (no static page needed)
$WP option update show_on_front 'posts'

echo "Pages created."

# Populate page content from template
echo "Populating page content..."
cat /var/www/html/scripts/populate-content.php | $WP eval-file -
echo "Page content populated."

# Import seed images BEFORE creating foster dogs so attachment slugs
# don't collide (fosters and images share names like "binky", "aiden")
echo "Importing seed images..."
SEED_DIR="/var/www/html/web/app/uploads/seed"
if [ -d "$SEED_DIR" ] && [ -n "$(ls "$SEED_DIR"/ 2>/dev/null)" ]; then
  for img in "$SEED_DIR"/*; do
    TITLE=$(basename "$img" | sed 's/\.[^.]*$//' | tr '-' ' ')
    ATTACH_ID=$($WP media import "$img" --title="$TITLE" --porcelain 2>/dev/null) || true
    case "$(basename "$img")" in
      sgalogo-1.png)
        if [ -n "$ATTACH_ID" ]; then
          $WP option update site_logo "$ATTACH_ID"
          $WP option update site_icon "$ATTACH_ID"
          echo "Site logo set (attachment ID: $ATTACH_ID)."
        fi
        ;;
    esac
  done
  echo "Seed images imported."
else
  echo "Warning: No seed images found at $SEED_DIR — skipping."
fi

# Populate foster dogs (after images so photo lookups succeed)
echo "Populating foster dogs..."
/bin/sh /var/www/html/scripts/populate-fosters.sh

# Create Editor-role accounts for content editors.
# Editors can create/edit pages, posts, and custom post types (foster dogs),
# but CANNOT access the Site Editor or change theme settings.
# This keeps git as the source of truth for templates and styles.
echo "Creating editor accounts..."
$WP user create lily lily@savinggreatanimals.org --role=editor --display_name='Lily Piecora' 2>/dev/null || true
$WP user create jacintha jacintha@savinggreatanimals.org --role=editor --display_name='Jacintha Sayed' 2>/dev/null || true

echo "Editor accounts created (lily, jacintha). Set passwords in wp-admin."

echo "=== Setup complete ==="
echo "Site: http://localhost:8080"
echo "Admin: http://localhost:8080/wp/wp-admin (admin / admin)"
