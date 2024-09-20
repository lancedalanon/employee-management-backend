FROM php:8.2-fpm

# Install dependencies for building PHP extensions and Nginx
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libonig-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install necessary PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring

COPY . .

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]
