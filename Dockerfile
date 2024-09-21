# Use the official PHP image with the required version
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    nginx \
    libpq-dev \
    libcurl4-openssl-dev \ 
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd \
        zip \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mysqli \
        opcache \
        bcmath \
        ctype \
        curl \
        dom \
        fileinfo \
        filter \
        hash \
        mbstring \
        openssl \
        pcre \
        session \
        tokenizer \
        xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the Laravel application files
COPY . .

# Set proper permissions for the application directory
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    find /var/www/storage -type f -exec chmod 664 {} \; && \
    find /var/www/storage -type d -exec chmod 775 {} \; && \
    find /var/www/bootstrap/cache -type f -exec chmod 664 {} \; && \
    find /var/www/bootstrap/cache -type d -exec chmod 775 {} \;

# Install Laravel application dependencies
RUN composer install --optimize-autoloader --no-dev

# Run Artisan commands
RUN php artisan optimize:clear && \
    php artisan storage:link

# Copy Nginx configuration file from the main directory
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

# Expose the port the app runs on
EXPOSE 80

# Start the PHP-FPM and Nginx services
CMD service nginx start && php-fpm
