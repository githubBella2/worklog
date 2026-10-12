#!/bin/sh
set -e

# Render memberi nomor port lewat variabel PORT
sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf

# Siapkan database dan cache
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan web server
exec apache2-foreground
