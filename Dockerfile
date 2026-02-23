FROM php:8.4-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libcurl4-openssl-dev \
        libicu-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_sqlite \
        gd \
        curl \
        intl \
    && rm -rf /var/lib/apt/lists/*

# Set DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 12M" >> "$PHP_INI_DIR/php.ini" \
    && echo "session.cookie_httponly = 1" >> "$PHP_INI_DIR/php.ini" \
    && echo "session.cookie_samesite = Lax" >> "$PHP_INI_DIR/php.ini"

# Create data & uploads directories
RUN mkdir -p /var/www/html/data /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/public/uploads

WORKDIR /var/www/html

# Entrypoint to fix bind-mount permissions at runtime
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 80
