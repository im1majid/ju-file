<?php
/** تسجيل استخدام الأدوات التي تعمل داخل المتصفح (ffmpeg / pdf). */
require __DIR__ . '/../includes/init.php';

if (!is_post()) {
    json_response(['error' => 'الطريقة غير مسموحة.'], 405);
}
verify_csrf(json: true);

$category = (string) ($_POST['category'] ?? '');
$tool     = (string) ($_POST['tool'] ?? '');
if (!isset(tool_categories()[$category]['tools'][$tool])) {
    json_response(['error' => 'أداة غير معروفة.'], 400);
}
log_operation($category, $tool, isset($_POST['file_name']) ? clean_filename((string) $_POST['file_name']) : null);
json_response(['ok' => true]);

