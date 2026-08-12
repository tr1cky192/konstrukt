FROM php:8.2-cli

WORKDIR /var/www/html

COPY . .


CMD php -S 0.0.0.0:${PORT:-8000} router.php