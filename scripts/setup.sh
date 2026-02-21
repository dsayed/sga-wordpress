#!/usr/bin/env bash
set -euo pipefail

# SGA WordPress Setup Script
# Bootstraps a fresh WordPress install with the correct theme, plugins, and settings.
# Usage: docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/setup.sh

WP="wp --allow-root --path=/var/www/html/web/wp"

echo "=== SGA WordPress Setup ==="

# Wait for database (simple query test — db check has TLS issues with wordpress:cli image)
echo "Waiting for database..."
until $WP eval "echo 'db ok';" > /dev/null 2>&1; do
  sleep 2
done
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
$WP theme activate twentytwentyfive

# Activate plugins
echo "Activating plugins..."
$WP plugin activate gutenverse 2>/dev/null || true

# Set basic options
echo "Configuring site options..."
$WP option update blogdescription "The Right Dog For The Right Home"
$WP option update timezone_string "America/Los_Angeles"
$WP option update date_format "F j, Y"
$WP option update permalink_structure "/%postname%/"

# Remove default content
echo "Cleaning default content..."
$WP post delete 1 --force 2>/dev/null || true
$WP post delete 2 --force 2>/dev/null || true
$WP comment delete 1 --force 2>/dev/null || true

echo "=== Setup complete ==="
echo "Site: http://localhost:8080"
echo "Admin: http://localhost:8080/wp/wp-admin (admin / admin)"
