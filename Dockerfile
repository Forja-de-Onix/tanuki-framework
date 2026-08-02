# Tanuki Framework — App container (PHP-FPM)
FROM php:8.1-fpm

ARG INSTALL_DEV_DEPS=false

# System dependencies for the PHP extensions below
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Core PHP extensions: MySQL + PostgreSQL PDO drivers, zip (needed by Composer)
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Optional: Redis extension (uncomment to enable Redis::connect())
# RUN pecl install redis && docker-php-ext-enable redis

# Optional: MongoDB extension (uncomment to enable Mongo::connect())
# RUN pecl install mongodb && docker-php-ext-enable mongodb

# Xdebug — only installed when building for development (see docker-compose.override.yml)
RUN if [ "$INSTALL_DEV_DEPS" = "true" ]; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

# Composer (needed for PHPUnit, mongodb/mongodb, etc.)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency manifests first for better layer caching
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then \
        if [ "$INSTALL_DEV_DEPS" = "true" ]; then \
            composer install --no-interaction --no-scripts; \
        else \
            composer install --no-dev --no-interaction --no-scripts; \
        fi \
    fi

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]