# PHP 8.3 FPM Base Image
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y 
    git 
    curl 
    unzip 
    zip 
    libpng-dev 
    libjpeg-dev 
    libfreetype6-dev 
    libonig-dev 
    libxml2-dev 
    libzip-dev 
    libicu-dev 
    && docker-php-ext-configure gd --with-freetype --with-jpeg 
    && docker-php-ext-install 
    pdo_mysql 
    mbstring 
    exif 
    pcntl 
    bcmath 
    gd 
    intl 
    zip 
    opcache 
    && apt-get clean 
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]