<?php
/**
 * إعدادات التطبيق — عدّل قيم قاعدة البيانات حسب جهازك (افتراضيات XAMPP).
 * يمكن تجاوز أي قيمة بمتغير بيئة بنفس الاسم (مفيد للاختبار والنشر).
 */
function env_or(string $key, string $default): string
{
    $v = getenv($key);
    return $v === false ? $default : $v;
}

define('DB_HOST', env_or('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env_or('DB_PORT', '3306'));
define('DB_NAME', env_or('DB_NAME', 'ju_file'));
define('DB_USER', env_or('DB_USER', 'root'));
define('DB_PASS', env_or('DB_PASS', ''));

const APP_NAME = 'Ju File';
const MAX_UPLOAD_MB = 50;          // أقصى حجم لملف واحد يُرفع للخادم (أدوات الخادم + المشاركة)
const MAX_IMAGE_PIXELS = 40000000; // حماية الذاكرة عند معالجة الصور (40 ميجابكسل)
const STORAGE_DIR = __DIR__ . '/storage/uploads';

