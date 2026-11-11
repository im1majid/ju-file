<?php
/**
 * صفحة أدوات قسم (صور/فيديو/صوت/مستندات) — قالب واحد يُعاد استخدامه.
 * يتطلب تعريف $category قبل التضمين (انظر image.php مثلاً).
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/components.php';

$cat = tool_categories()[$category] ?? null;
if (!$cat) {
    http_response_code(404);
    exit('Not found');
}

$engines = array_unique(array_column($cat['tools'], 'engine'));
$pageScripts = [];
if (in_array('ffmpeg', $engines, true)) {
    $pageScripts = ['assets/vendor/ffmpeg.js', 'assets/js/media.js'];
}
if (in_array('pdf', $engines, true)) {
    $pageScripts = ['assets/vendor/pdf-lib.min.js', 'assets/js/pdf.js'];
}

// إعدادات الأدوات لجافاسكربت (بدون الحقول، فهي مرسومة في HTML)
$jsTools = [];
foreach ($cat['tools'] as $key => $t) {
    $jsTools[$key] = [
        'title'   => $t['title'],
        'desc'    => $t['desc'],
        'engine'  => $t['engine'],
        'accept'  => $t['accept'],
        'multi'   => $t['multi'] ?? false,
        'cropUi'  => $t['crop_ui'] ?? false,
    ];
}

$pageTitle = 'أدوات ' . $cat['title'];
$activePage = $category;
require __DIR__ . '/header.php';
?>

<header class="mb-4">
    <nav class="small mb-2"><a href="index.php" class="link-secondary text-decoration-none">الرئيسية</a> <span class="text-secondary">/ <?= e($cat['title']) ?></span></nav>
    <h1 class="h3 fw-bold"><i class="bi <?= e($cat['icon']) ?> text-accent"></i> أدوات <?= e($cat['title']) ?></h1>
    <p class="text-secondary mb-0"><?= e($cat['desc']) ?></p>
</header>

<div class="tool-grid mb-4" role="tablist">
    <?php foreach ($cat['tools'] as $key => $t): ?>
        <button type="button" class="tool-chip" data-tool="<?= e($key) ?>" role="tab">
            <i class="bi <?= e($t['icon']) ?>"></i><span><?= e($t['title']) ?></span>
        </button>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel h-100">
            <label class="dropzone" id="dropzone">
                <input type="file" id="fileInput" hidden>
                <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                <span class="fw-bold mt-2">اسحب الملف هنا أو اضغط للاختيار</span>
                <small class="text-secondary" id="acceptHint"></small>
            </label>
            <div id="preview" class="mt-3"></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h5 fw-bold mb-1" id="toolTitle"></h2>
            <p class="small text-secondary" id="toolDesc"></p>
            <?php foreach ($cat['tools'] as $key => $t): ?>
                <form class="tool-form d-none" id="form-<?= e($key) ?>" data-tool="<?= e($key) ?>">
                    <?= csrf_field() ?>
                    <?php foreach ($t['fields'] as $field) {
                        echo render_field($field, $key);
                    } ?>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-play-fill"></i> تنفيذ</button>
                </form>
            <?php endforeach; ?>
            <div id="status" class="mt-3"></div>
            <p class="small text-secondary mt-3 mb-0" id="engineNote"></p>
        </div>
    </div>
</div>

<section id="result" class="panel mt-4 d-none" aria-live="polite"></section>

<script>
    window.JU_PAGE = <?= json_encode([
        'category'  => $category,
        'tools'     => $jsTools,
        'maxUpload' => upload_limit_bytes(),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<?php require __DIR__ . '/footer.php'; ?>

