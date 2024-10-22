# Employee Management System (API-Only Backend)

This is an API-only backend built with **Laravel 11** for managing employees. It includes features such as **Daily Time Record (DTR)**, **Kanban Board**, **Leave Requests**, and a **Company Dashboard**, among others.

## Features
- **Daily Time Record (DTR)**: Track employee attendance and time entries.
- **Kanban Board**: Manage tasks and projects effectively.
- **Leave Requests**: Handle and manage employee leave requests seamlessly.
- **Company Dashboard**: View insights and key company metrics in real-time.
- **API-Only**: Built as a RESTful API for easy integration with frontend clients.

## Table of Contents
1. [Installation](#installation)
2. [Docker Setup for Local Development](#docker-setup-for-local-development)
3. [Hosting on Render](#hosting-on-render)
4. [GitHub Actions CI/CD](#github-actions-cicd)
5. [License](#license)
6. [Contact](#contact)

## Installation

To install and run the project locally, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/employee-management-system.git
   cd employee-management-system
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

This project includes a **`.yml`** file for GitHub Actions, which automates Continuous Integration and Deployment (CI/CD) workflows. Every time you push code to the repository, the following steps are executed:

1. **Install Dependencies**: Composer installs Laravel and its dependencies.
2. **Run Unit and Feature Tests**: PHPUnit tests are run to ensure the functionality of the system. This includes both unit and feature testing of the API.
3. **Deployment to Render**: After successful testing, the application can be deployed to Render (if configured).

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
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction --no-scripts --no-progress

      - name: Run migrations
        run: php artisan migrate --env=testing

      - name: Run Tests
        run: php artisan test --env=testing
```

## License

This project is licensed under the **MIT License** - see the LICENSE file for details.

## Contact

For any questions or feedback, feel free to reach out:

- **Email**: lanceorville5@gmail.com
- **GitHub**: [lancedalanon](https://github.com/lancedalanon)
