<?php
/**
 * نقطة البداية المشتركة: كل صفحة تبدأ بـ require هذا الملف.
 * يحمّل الإعدادات، يبدأ الجلسة بإعدادات آمنة، ويرسل ترويسات الحماية.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

session_name('jufile_sid');
session_set_cookie_params([
    'httponly' => true,                     // لا يمكن لجافاسكربت قراءة الكوكي
    'samesite' => 'Lax',                    // طبقة إضافية ضد CSRF
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

