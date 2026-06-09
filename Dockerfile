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
 && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql pgsql zip \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ensure PDO and PostgreSQL extensions are explicitly installed
RUN docker-php-ext-install pdo pdo_pgsql pgsql || true

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

# Suppress Apache FQDN warning in container logs
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Runtime PHP limits/settings for larger CSV/XLSX uploads and cleaner API output.
RUN printf '%s\n' \
    'upload_max_filesize=32M' \
    'post_max_size=32M' \
    'max_file_uploads=20' \
    'display_errors=Off' \
    'html_errors=Off' \
    'log_errors=On' \
    > /usr/local/etc/php/conf.d/uploads.ini

# Change Apache to listen on 8080 for Cloud Run
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
