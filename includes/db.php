<?php
/** اتصال واحد بقاعدة البيانات عبر PDO (يُنشأ عند أول استخدام فقط). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // استعلامات مُجهّزة حقيقية
            ]);
        } catch (PDOException $ex) {
            error_log('DB connection failed: ' . $ex->getMessage());
            http_response_code(500);
            exit('<!doctype html><meta charset="utf-8"><div dir="rtl" style="font-family:sans-serif;max-width:560px;margin:80px auto">'
                . '<h2>تعذّر الاتصال بقاعدة البيانات</h2>'
                . '<p>تأكد من تشغيل MySQL، ثم استورد الملف <code>database.sql</code>، وراجع القيم في <code>config.php</code>.</p></div>');
        }
    }
    return $pdo;
}

