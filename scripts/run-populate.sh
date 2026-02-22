#!/bin/sh
# Wrapper to run populate-content.php via stdin pipe
# wp eval-file silently fails with direct file paths, but works when piped via stdin
WP="wp --allow-root --path=/var/www/html/web/wp"
cat /var/www/html/scripts/populate-content.php | $WP eval-file -
