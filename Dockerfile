# Base image for PHP-FPM
FROM php:8.3-fpm

# Suppress interactive prompts
ARG DEBIAN_FRONTEND=noninteractive

# Install system dependencies and PHP extensions prerequisites
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
     libpq-dev libzip-dev zlib1g-dev libxml2-dev libonig-dev \
     libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
     unzip git curl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
      pdo_pgsql mbstring bcmath xml zip sockets gd \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code from host (mounted via docker-compose)
COPY src/ /var/www/html

# Ensure storage and cache directories exist and set permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Install PHP dependencies
RUN composer install --prefer-dist --no-dev --no-interaction --optimize-autoloader

# Generate application key (if not already set)
RUN php artisan key:generate --ansi || true

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
