FROM php:8.1-apache

# Install system deps for PDO MySQL/Postgres and common utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql zip \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies (will use composer.lock if present)
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
