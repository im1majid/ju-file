<?php
/** تنزيل/عرض الملف المشارك بعد التحقق من الصلاحية وكلمة المرور. */
require __DIR__ . '/includes/init.php';

$share = find_share((string) ($_GET['t'] ?? ''));
if (!$share || $share['expired'] || !is_file(STORAGE_DIR . '/' . $share['stored_name'])) {
    http_response_code(404);
    exit('الملف غير موجود أو انتهت صلاحيته.');
}
if (!share_unlocked($share)) {
    redirect('f.php?t=' . $share['token']);
}

// العرض داخل الصفحة فقط للوسائط الآمنة؛ غيرها يُنزّل دائماً (حماية من XSS عبر ملفات HTML/SVG)
$safeInline = preg_match('#^(image/(jpeg|png|gif|webp)|video/[\w.+-]+|audio/[\w.+-]+)$#', $share['mime']);
$inline = !empty($_GET['inline']) && $safeInline;

if (!$inline) {
    db()->prepare('UPDATE shares SET downloads = downloads + 1 WHERE id = ?')->execute([$share['id']]);
}

$path = STORAGE_DIR . '/' . $share['stored_name'];
$name = $share['original_name'];
header('Content-Type: ' . ($inline ? $share['mime'] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . preg_replace('/[^\x20-\x7E]|"/', '_', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header("Content-Security-Policy: default-src 'none'; sandbox");
header('Cache-Control: private, max-age=0');
readfile($path);

