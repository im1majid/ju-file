<?php
/**
 * إعدادات التطبيق — يدعم التشغيل المحلي (XAMPP) والسحابي (Railway / Render / Docker) تلقائياً.
 */
function env_first(array $keys, string $default): string
{
    foreach ($keys as $k) {
        $v = getenv($k);
        if ($v !== false && $v !== '') {
            return $v;
        }
    }
    return $default;
}

define('DB_HOST', env_first(['DB_HOST', 'MYSQLHOST', 'MARIADB_HOST'], '127.0.0.1'));
define('DB_PORT', (int) env_first(['DB_PORT', 'MYSQLPORT', 'MARIADB_PORT'], '3306'));
define('DB_NAME', env_first(['DB_NAME', 'MYSQLDATABASE', 'MARIADB_DATABASE'], 'ju_file'));
define('DB_USER', env_first(['DB_USER', 'MYSQLUSER', 'MARIADB_USER'], 'root'));
define('DB_PASS', env_first(['DB_PASS', 'MYSQLPASSWORD', 'MARIADB_PASSWORD'], ''));

const APP_NAME = 'Ju File';
const MAX_UPLOAD_MB = 50;          // أقصى حجم لملف واحد يُرفع للخادم (أدوات الخادم + المشاركة)
const MAX_IMAGE_PIXELS = 40000000; // حماية الذاكرة عند معالجة الصور (40 ميجابكسل)
const STORAGE_DIR = __DIR__ . '/storage/uploads';
