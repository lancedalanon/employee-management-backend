# Stage 1: Build the PHP application
FROM php:8.2-fpm-alpine AS build

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
    libzip-dev \
    supervisor

# Install PHP extensions for Laravel & PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip gd pcntl xml

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Install Laravel dependencies using Composer
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Final Nginx + PHP-FPM with supervisord
FROM nginx:alpine AS production

WORKDIR /var/www/html

# Install supervisor and create www-data user
RUN apk add --no-cache supervisor && \
    addgroup -S www-data && adduser -S www-data -G www-data

# Copy built PHP application from the previous stage
COPY --from=build /var/www/html /var/www/html

# Copy custom configuration files from the root directory
COPY ./nginx.conf /etc/nginx/nginx.conf             
COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf   
COPY ./supervisord.conf /etc/supervisord.conf       

# Set correct permissions for the application files
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start supervisord to manage both services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
