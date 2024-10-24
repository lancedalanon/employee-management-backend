# Employee Management System (API-Only Backend)

This is an API-only backend built with **Laravel 11** for managing employees. It includes features such as **Daily Time Record (DTR)**, **Kanban Board**, **Leave Requests**, and a **Company Dashboard**, among others.

## Features
- **Daily Time Record (DTR)**: Track employee attendance and time entries.
- **Kanban Board**: Manage tasks and projects effectively.
- **Leave Requests**: Handle and manage employee leave requests seamlessly.
- **Company Dashboard**: View insights and key company metrics in real-time.
- **API-Only**: Built as a RESTful API for easy integration with frontend clients.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Docker Setup for Local Development](#docker-setup-for-local-development)
4. [Hosting on Render](#hosting-on-render)
5. [GitHub Actions CI/CD](#github-actions-cicd)
6. [License](#license)
7. [Contact](#contact)

## Prerequisites

Before you begin, ensure you have met the following requirements:

- **PHP 8.2** or higher
- **Laravel 11**
- **Composer** (for managing PHP dependencies)
- **MySQL** or **PostgreSQL** (for database)
- Optional: **Docker** (if you prefer containerized development)

If you choose to use Docker, follow the Docker setup instructions. Otherwise, ensure you manually install all the required dependencies listed above.

## Installation

To install and run the project locally, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/lancedalanon/employee-management-backend.git
   cd employee-management-backend
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Set up environment variables**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update the `.env` file** with your database configuration. Ensure the following fields are set:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations** (you can also seed with `php artisan migrate:fresh --seed`):
   ```bash
   php artisan migrate
   ```

6. **Generate JWT secret**:
   ```bash
   php artisan jwt:secret
   ```

7. **Add JWT secret to `.env` file**:
   ```env
   JWT_SECRET=your_generated_secret_key
   ```

8. **Run the application locally**:
   ```bash
   php artisan serve
   ```

### Requirements
- **PHP 8.2** or higher
- **Laravel 11**
- **Composer**
- **MySQL or PostgreSQL** (for database)

## Docker Setup for Local Development

You can set up the project using Docker for local development with the following configuration.

### Dockerfile
```dockerfile
# Use the official PHP image
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www

# Expose port 9000 and start PHP-FPM server
EXPOSE 9000
CMD ["php-fpm"]
```

### Running the Project in Docker
1. **Build the Docker image**:
   ```bash
   docker build -t employee-management-backend .
   ```

2. **Run the Docker container**:
   ```bash
   docker run -p 8000:9000 employee-management-backend
   ```

This setup will allow you to develop and run the application locally using Docker.

## Hosting on Render

You can host this Laravel project on **Render**, a popular platform-as-a-service (PaaS) for web applications. Follow Render's Laravel web service setup instructions for deployment.

## GitHub Actions CI/CD

This project includes a **GitHub Actions** workflow for Continuous Integration (CI) to automate testing whenever changes are pushed to the repository. The workflow is configured to run tests on the **main** branch and during pull requests.

### Example GitHub Actions Workflow (`.github/workflows/laravel.yml`)

```yaml
name: Laravel CI

on:
  push:
    branches:
      - main
  pull_request:
    branches:
      - main

jobs:
  laravel-tests:
    runs-on: ubuntu-latest

    steps:
      # Checkout code
      - uses: actions/checkout@v4

      # Set up PHP
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, sqlite3, dom, fileinfo

      # Cache Composer dependencies for faster builds
      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: composer-

      # Install Composer dependencies
      - name: Install Composer dependencies
        run: composer install --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

      # Set environment variables for testing
      - name: Set environment variables
        run: |
          echo "APP_ENV=testing" >> $GITHUB_ENV
          echo "DB_CONNECTION=sqlite" >> $GITHUB_ENV
          echo "JWT_SECRET=your_jwt_secret" >> $GITHUB_ENV

      # Copy .env.example and prepare the environment
      - name: Copy .env
        run: cp .env.example .env

      # Generate APP_KEY (required for Crypt and other encrypted functions)
      - name: Generate application key
        run: php artisan key:generate

      # Create SQLite database
      - name: Prepare SQLite database
        run: |
          touch database/database.sqlite

      # Run migrations for testing
      - name: Run migrations
        run: php artisan migrate --force

      # Run tests
      - name: Run tests
        run: php artisan test --env=testing --debug
```
## License

This project is licensed under the **MIT License** - see the LICENSE file for details.

## Contact

For any questions or feedback, feel free to reach out:

- **Email**: lanceorville5@gmail.com
- **GitHub**: [lancedalanon](https://github.com/lancedalanon)
