# Use the official PHP image as the base image
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PostgreSQL client libraries
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim unzip git curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    nginx \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring zip exif pcntl opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy existing application directory contents
COPY . /var/www/html

# Give permissions to the application
RUN chown -R www-data:www-data /var/www/html

# Install project dependencies
RUN composer install --no-dev

# Cache Laravel configuration and routes
RUN php artisan config:cache && \
    php artisan route:cache

# Copy the default Nginx configuration file
COPY ./default.conf /etc/nginx/conf.d/default.conf

# Copy the cron job file
COPY ./cronjobs /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron
RUN crontab /etc/cron.d/laravel-cron

# Create the storage link
RUN php artisan storage:link

# Expose port 80
EXPOSE 80

# Start Nginx and PHP services
CMD ["sh", "-c", "service nginx start && php-fpm && cron -f"]
