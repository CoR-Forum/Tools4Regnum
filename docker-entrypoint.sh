#!/bin/sh
set -e

# Ensure www-data can write to bind-mounted directories
chown -R www-data:www-data /var/www/html/data /var/www/html/public/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/data /var/www/html/public/uploads 2>/dev/null || true

# Run the default Apache entrypoint
exec apache2-foreground
