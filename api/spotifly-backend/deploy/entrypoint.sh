#!/bin/bash

# Check if Laravel has been initialized
# if not then run the initialization script
FLAG_FILE="/var/www/html/.laravel_initialized"

if [ ! -f "$FLAG_FILE" ]; then
    echo "Running Laravel initialization..."

    composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader

    php artisan key:generate --quiet
    php artisan storage:link
    php artisan config:cache
    php artisan migrate

    # Create a flag file to indicate initialization has been done
    touch "$FLAG_FILE"
    echo "Laravel initialization complete."
else
    echo "Laravel has already been initialized. Skipping."
fi

# Configuring permissions to storage
if [ -d "/var/www/html/storage" ]; then
    chown -R daemon /var/www/html/storage
fi

chown -R daemon /var/www/html/bootstrap/cache

# store envirnoment variables
echo "Writing project's environment variables to .env file"
cat /dev/null > /etc/environment && env >> /etc/environment

touch /var/log/cron.log

# Start crontab
cron

# Start supervisor
/usr/bin/supervisord
