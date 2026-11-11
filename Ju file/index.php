<?php
/** الصفحة الرئيسية: مقدمة واضحة + اختيار نوع الملف للذهاب لصفحته المتخصصة + إحصائيات حية من قاعدة البيانات. */
require __DIR__ . '/includes/init.php';

$cats = tool_categories();

// جلب إحصائيات عامة من قاعدة البيانات (Dynamic Data Display)
$totalOps    = (int) db()->query('SELECT COUNT(*) FROM operations')->fetchColumn();
$totalShares = (int) db()->query('SELECT COUNT(*) FROM shares WHERE expires_at IS NULL OR expires_at > NOW()')->fetchColumn();
$catCounts   = db()->query('SELECT category, COUNT(*) AS cnt FROM operations GROUP BY category')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'كل أدوات ملفاتك في مكان واحد';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero text-center py-4 py-lg-5 mb-4">
    <span class="badge rounded-pill bg-accent-soft text-accent px-3 py-2 mb-3">
        <i class="bi bi-magic"></i> بسيط · سريع · آمن
    </span>
    <h1 class="display-6 fw-bold mb-3">اختر نوع الملف وابدأ التعديل فوراً</h1>
    <p class="lead text-secondary mx-auto mb-4" style="max-width:640px">
        منصة متخصصة لتعديل الملفات، تحويل الصيغ، وتصغير الحجم — مع إمكانية تحويل أي ملف إلى رابط مباشر أو رمز QR للمشاركة السريعة.
    </p>
    <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="share.php" class="btn btn-accent btn-lg px-4">
            <i class="bi bi-qr-code"></i> حوّل ملفاً إلى رابط أو QR
        </a>
        <?php if (!current_user()): ?>
            <a href="register.php" class="btn btn-outline-dark btn-lg px-4">حساب مجاني لحفظ روابطك</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-grid"></i> لوحة ملفاتي</a>
        <?php endif; ?>
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">ماذا تريد أن تعدّل اليوم؟</h2>
        <span class="small text-secondary">تظهر لك الخيارات المتاحة لكل نوع ملف تحديداً</span>
    </div>

    <div class="row g-3">
        <?php foreach ($cats as $key => $cat): ?>
            <div class="col-md-6 col-lg-3">
                <a class="cat-card panel h-100 d-flex flex-column text-decoration-none text-reset" href="<?= e($cat['page']) ?>">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="cat-icon"><i class="bi <?= e($cat['icon']) ?>"></i></div>
                        <span class="badge text-bg-light"><?= count($cat['tools']) ?> أدوات</span>
                    </div>
                    <h3 class="h5 fw-bold mb-1"><?= e($cat['title']) ?></h3>
                    <p class="small text-secondary mb-3 flex-grow-1"><?= e($cat['desc']) ?></p>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <?php foreach ($cat['tools'] as $t): ?>
                            <span class="mini-tag"><?= e($t['title']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small fw-bold text-accent mt-auto pt-2 border-top">
                        <span>فتح أدوات <?= e($cat['title']) ?></span>
                        <i class="bi bi-arrow-left"></i>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <h2 class="h5 fw-bold mb-2"><i class="bi bi-qr-code-scan text-accent"></i> مشاركة أي ملف برابط أو رمز QR</h2>
            <p class="text-secondary mb-3">
                سواء عدّلت ملفك هنا أو كان لديك ملف جاهز على جهازك، يمكنك تحويله خلال ثوانٍ إلى رابط قصير ورمز QR قابل للمسح بالجوال، مع خيار تحديد مدة صلاحية أو كلمة مرور.
            </p>
            <a href="share.php" class="btn btn-accent"><i class="bi bi-upload"></i> ارفع ملفاً للمشاركة</a>
        </div>
        <div class="col-lg-5">
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent"><?= $totalOps ?></div>
                        <div class="small text-secondary">عملية معالجة منفّذة</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent"><?= $totalShares ?></div>
                        <div class="small text-secondary">رابط مشاركة نشط</div>
                    </div>
                </div>
                <?php foreach ($cats as $key => $cat): ?>
                    <div class="col-3">
                        <div class="p-2 rounded-3 border small">
                            <div class="fw-bold"><?= (int) ($catCounts[$key] ?? 0) ?></div>
                            <div class="text-secondary" style="font-size:.75rem"><?= e($cat['title']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

