# حاوية جاهزة للنشر السحابي المباشر من GitHub (Render / Railway / Koyeb / Fly.io)
# تشمل PHP 8.3 + Apache + جميع الامتدادات المطلوبة + MariaDB مدمجة تعمل تلقائياً إن لم تُربط قاعدة خارجية
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        mariadb-server \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip mbstring exif \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# رفع حدود حجم الملفات المرفوعة والذاكرة
RUN { \
        echo "upload_max_filesize=60M"; \
        echo "post_max_size=65M"; \
        echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/jufile.ini

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage \
    && sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 8080
CMD ["/var/www/html/docker-entrypoint.sh"]
