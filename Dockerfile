# Base image
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk update && apk add --no-cache \
    git \
    curl \
    bash \
    linux-headers \
    autoconf \
    make \
    g++ \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    libzip-dev \
    nodejs \
    npm

# Configure GD
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ \
    --with-webp=/usr/include/

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create user for Laravel
RUN addgroup -g 1000 www \
    && adduser -D -u 1000 -G www www

# Copy application files
COPY . /var/www

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install and build Node.js dependencies
RUN npm install && npm run build

# Copy environment file if example exists
RUN if [ -f .env.example ]; then cp .env.example .env; fi

# Supervisor config
COPY docker-compose/supervisor/websockets.conf /etc/supervisor.d/websockets.ini

# Custom php.ini
COPY docker-compose/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Switch to "www" user
USER www

EXPOSE 9000

CMD ["php-fpm"]
