#!/usr/bin/env bash
set -e

# ضبط منفذ Apache حسب متغير PORT الذي يمرره Railway (افتراضياً 8080)
PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# تجهيز .env إن لم يكن موجوداً
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# ربط متغيرات Railway MySQL تلقائياً مع Laravel
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-${MYSQLHOST:-127.0.0.1}}"
export DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
export DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-ju_file}}"
export DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-root}}"
export DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

# إذا لم تُربط قاعدة MySQL خارجية، نشغّل MariaDB المدمجة داخل الحاوية تلقائياً
if [ -z "${MYSQLHOST}" ] && [ "${DB_HOST}" = "127.0.0.1" ]; then
    mkdir -p /var/run/mysqld
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql
    service mariadb start
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;" || true
fi

# توليد APP_KEY إن لم يكن مضبوطاً
if [ -z "${APP_KEY}" ] && ! grep -qE '^APP_KEY=base64:' /var/www/html/.env; then
    php /var/www/html/artisan key:generate --force
fi

# تنفيذ هجرات قاعدة البيانات وتنظيف الكاش
php /var/www/html/artisan migrate --force || true
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan view:cache || true

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ضمان تفعيل وحدة MPM واحدة فقط لـ Apache لتفادي خطأ AH00534
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec apache2-foreground
