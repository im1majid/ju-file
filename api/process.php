<?php
/**
 * واجهة معالجة الملفات على الخادم (تُستدعى عبر fetch من صفحات الأدوات).
 * النجاح: يرجع الملف الناتج مباشرة | الفشل: JSON فيه رسالة الخطأ.
 */
require __DIR__ . '/../includes/init.php';

/** خطأ متوقع يُعرض للمستخدم كما هو. */
class ToolError extends RuntimeException {}

require __DIR__ . '/../includes/handlers/image.php';
require __DIR__ . '/../includes/handlers/text.php';
require __DIR__ . '/../includes/handlers/zip.php';

if (!is_post()) {
    json_response(['error' => 'الطريقة غير مسموحة.'], 405);
}
verify_csrf(json: true);

$category = (string) ($_POST['category'] ?? '');
$toolKey  = (string) ($_POST['tool'] ?? '');
$tool     = tool_categories()[$category]['tools'][$toolKey] ?? null;
if (!$tool || $tool['engine'] !== 'server') {
    json_response(['error' => 'أداة غير معروفة.'], 400);
}

// 1) التحقق من الخيارات حسب تعريف الحقول
[$opts, $errors] = validate_fields($tool['fields'], $_POST);

// 2) التحقق من الملفات
$files = uploaded_files('files');
$multi = $tool['multi'] ?? false;
if (!$files) {
    $errors[] = 'اختر ملفاً أولاً.';
} elseif (!$multi && count($files) > 1) {
    $errors[] = 'هذه الأداة تقبل ملفاً واحداً فقط.';
} elseif (count($files) > 20) {
    $errors[] = 'الحد الأقصى 20 ملفاً في المرة الواحدة.';
}
foreach ($files as $f) {
    if ($err = upload_error($f)) {
        $errors[] = $err;
    } elseif ($tool['accept'] && !in_array(file_ext($f['name']), $tool['accept'], true)) {
        $errors[] = 'صيغة «' . $f['name'] . '» غير مدعومة. الصيغ المقبولة: ' . implode('، ', $tool['accept']);
    }
}
if ($errors) {
    json_response(['error' => implode("\n", $errors)], 422);
}

// 3) التنفيذ
try {
    $result = match ($category) {
        'image'    => image_tool($toolKey, $files[0], $opts),
        'document' => $toolKey === 'zip' ? zip_tool($files, $opts) : text_tool($toolKey, $files[0], $opts),
    };
} catch (ToolError $ex) {
    json_response(['error' => $ex->getMessage()], 422);
} catch (Throwable $ex) {
    error_log('Tool failed: ' . $ex);
    json_response(['error' => 'حدث خطأ غير متوقع أثناء المعالجة.'], 500);
}

log_operation($category, $toolKey, $files[0]['name']);

if (isset($result['json'])) {
    json_response($result['json']);
}
header('Content-Type: ' . $result['mime']);
header('Content-Length: ' . strlen($result['data']));
header('X-File-Name: ' . rawurlencode($result['name']));
if (!empty($result['info'])) {
    header('X-Info: ' . rawurlencode($result['info']));
}
header('Cache-Control: no-store');
echo $result['data'];
