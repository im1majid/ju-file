# حاوية جاهزة للنشر السحابي المباشر من GitHub على Railway / Render (Laravel 12 + PHP 8.3 + Apache)
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        mariadb-server \
        unzip \
        git \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip mbstring exif \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# توجيه Apache إلى مجلد public الخاص بـ Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# رفع حدود حجم الملفات المرفوعة والذاكرة
RUN { \
        echo "upload_max_filesize=105M"; \
        echo "post_max_size=110M"; \
        echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/jufile.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html/

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p /var/www/html/storage/app/uploads \
                /var/www/html/storage/framework/cache/data \
                /var/www/html/storage/framework/sessions \
                /var/www/html/storage/framework/views \
                /var/www/html/storage/logs \
                /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 8080
CMD ["/var/www/html/docker-entrypoint.sh"]
