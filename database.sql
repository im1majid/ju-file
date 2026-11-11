-- مخطط قاعدة بيانات Ju File (MySQL / MariaDB)
-- للاستيراد: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS ju_file
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ju_file;

-- جدول المستخدمين (المصادقة عبر الجلسات + تجزئة كلمات المرور)
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(80)  NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الملفات المشاركة عبر رابط / QR
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

-- جدول سجل عمليات الأدوات (إحصائيات + سجل المستخدم)
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

