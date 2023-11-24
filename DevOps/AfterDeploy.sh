#!/bin/bash
cd /var/www/html/auto-deploy/bmi-api
echo 'set file permissions'
sudo chown -R ubuntu:ubuntu .
sudo chown -R www-data storage
sudo chmod -R u+x .
sudo chmod g+w -R storage
echo 'copying env file.'
cp -rf .env.staging .env
echo 'installing composer dependencies'
composer install --optimize-autoloader
echo 'running migration(forced)'
php artisan migrate --force --no-interaction
echo 'running essential artisan commands'
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan optimize:clear
