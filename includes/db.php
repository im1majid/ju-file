<?php
/** اتصال واحد بقاعدة البيانات عبر PDO مع تهيئة تلقائية للجداول على السيرفرات السحابية. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $ex) {
            // إذا كانت قاعدة البيانات غير منشأة بعد على السيرفر، نحاول إنشائها تلقائياً
            try {
                $rootDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
                $tmpPdo = new PDO($rootDsn, DB_USER, DB_PASS, $opts);
                $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', DB_NAME);
                $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
            } catch (PDOException $ex2) {
                error_log('DB connection failed: ' . $ex2->getMessage());
                http_response_code(500);
                exit('<!doctype html><meta charset="utf-8"><div dir="rtl" style="font-family:sans-serif;max-width:560px;margin:80px auto">'
                    . '<h2>تعذّر الاتصال بقاعدة البيانات</h2>'
                    . '<p>تأكد من تشغيل MySQL، ثم استورد الملف <code>database.sql</code>، وراجع القيم في <code>config.php</code>.</p></div>');
            }
        }
        ensure_schema($pdo);
    }
    return $pdo;
}

/** ينشئ الجداول تلقائياً إذا كانت قاعدة البيانات فارغة عند أول تشغيل على السحابة. */
function ensure_schema(PDO $pdo): void
{
    try {
        $pdo->query('SELECT 1 FROM shares LIMIT 1');
    } catch (PDOException) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name          VARCHAR(80)  NOT NULL,
                email         VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS shares (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id       INT UNSIGNED NULL,
                token         CHAR(16)     NOT NULL UNIQUE,
                title         VARCHAR(150) NULL,
                original_name VARCHAR(200) NOT NULL,
                stored_name   CHAR(32)     NOT NULL,
                external_url  VARCHAR(500) NULL,
                mime          VARCHAR(120) NOT NULL,
                size          BIGINT UNSIGNED NOT NULL,
                category      ENUM('image','video','audio','document','other') NOT NULL DEFAULT 'other',
                password_hash VARCHAR(255) NULL,
                downloads     INT UNSIGNED NOT NULL DEFAULT 0,
                expires_at    DATETIME     NULL,
                created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_shares_user (user_id, id),
                INDEX idx_shares_expires (expires_at),
                CONSTRAINT fk_shares_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS operations (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id    INT UNSIGNED NULL,
                category   VARCHAR(20)  NOT NULL,
                tool       VARCHAR(40)  NOT NULL,
                file_name  VARCHAR(255) NULL,
                created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ops_user (user_id, id),
                INDEX idx_ops_category (category),
                CONSTRAINT fk_ops_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
