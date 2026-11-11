#!/usr/bin/env bash
set -e

# ضبط منفذ Apache حسب متغير PORT الذي يمرره السيرفر السحابي (افتراضياً 8080)
PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# إذا لم يتم تحديد قاعدة بيانات خارجية، نشغّل MariaDB المدمجة داخل الحاوية تلقائياً
if [ -z "${DB_HOST}" ] && [ -z "${MYSQLHOST}" ] && [ -z "${MARIADB_HOST}" ]; then
    mkdir -p /var/run/mysqld
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql
    service mariadb start
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS ju_file CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;" || true
    mysql -u root ju_file < /var/www/html/database.sql || true
fi

exec apache2-foreground

