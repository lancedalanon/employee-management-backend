# Stage 1: Build the PHP application
FROM php:8.2-fpm-alpine AS build

# Set the working directory inside the container
WORKDIR /var/www/html

# Install system dependencies
RUN apk --no-cache add \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libpq-dev \
    libxml2-dev \
    bash \
    shadow \
    oniguruma-dev \
    autoconf \
    build-base \
    postgresql-dev \
    libzip-dev

# Install PHP extensions for Laravel & PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip gd pcntl xml

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Install Laravel dependencies using Composer
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Final Nginx + PHP-FPM
FROM nginx:alpine AS production

# Set working directory for Nginx
WORKDIR /var/www/html

# Copy built PHP application from the previous stage
COPY --from=build /var/www/html /var/www/html

# Copy custom Nginx configuration
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy PHP-FPM pool configuration
COPY ./docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set correct permissions for the application files
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Nginx and PHP-FPM services
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
