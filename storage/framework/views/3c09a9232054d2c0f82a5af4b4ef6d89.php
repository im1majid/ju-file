<?php
    use App\Services\ToolCatalog;
    $navCategories = ToolCatalog::categories();
    $locale = app()->getLocale();
    $isRtl  = $locale === 'ar';
    $nextLocale = $isRtl ? 'en' : 'ar';

    $jsI18N = [
        'locale'          => $locale,
        'copied'          => __('نُسخ'),
        'themeDarkTip'    => __('التبديل إلى الوضع الفاتح'),
        'themeLightTip'   => __('التبديل إلى الوضع الداكن'),
        'bytes'           => [__('بايت'), 'KB', 'MB', 'GB'],
        'supported'       => __('الصيغ المدعومة: :ext'),
        'allSupported'    => __('جميع الصيغ مدعومة'),
        'multiHint'       => __(' · يمكنك اختيار عدة ملفات'),
        'serverNote'      => __('تُعالج على الخادم — الحد الأقصى :size'),
        'browserNote'     => __('تُعالج مباشرة داخل متصفحك دون رفع الملف للخادم'),
        'unsupportedExt'  => __('صيغة غير مدعومة للملف «:name». الصيغ المسموحة: :ext'),
        'playPreview'     => __('تشغيل / إيقاف المعاينة'),
        'durationSec'     => __('المدة: :s ثانية'),
        'processErr'      => __('تعذّر تنفيذ العملية.'),
        'readImgErr'      => __('تعذّرت قراءة الصورة.'),
        'readFileFirst'   => __('اختر ملفاً أولاً قبل التنفيذ.'),
        'processing'      => __('جاري المعالجة...'),
        'genericErr'      => __('حدث خطأ أثناء المعالجة.'),
        'statsTitle'      => __('إحصائيات الملف'),
        'ready'           => __('جاهز: :name'),
        'savedPct'        => __('وفّرت :pct%'),
        'downloadFile'    => __('تنزيل الملف'),
        'shareBtn'        => __('تحويل إلى رابط و QR'),
        'tooLargeShare'   => __('حجم الملف الناتج (:size) أكبر من حد الرفع للمشاركة (:max).'),
        'creatingLink'    => __('جاري رفع الملف وإنشاء الرابط...'),
        'linkErr'         => __('تعذّر إنشاء الرابط.'),
        'directShareLbl'  => __('رابط المشاركة المباشر'),
        'copy'            => __('نسخ'),
        'open'            => __('فتح'),
        'downloadQr'      => __('تنزيل QR'),
        'loadingFfmpeg'   => __('جاري تحميل معالج الفيديو والصوت داخل متصفحك...'),
        'ffmpegErr'       => __('تعذّر تحميل مكتبة المعالجة داخل المتصفح.'),
        'readingFile'     => __('جاري قراءة الملف...'),
        'endGtStart'      => __('وقت النهاية يجب أن يكون أكبر من وقت البداية.'),
        'mediaFailed'     => __('تعذّرت معالجة الملف بهذه الإعدادات. جرّب صيغة أخرى أو ألغِ القص السريع.'),
        'pdfNotLoaded'    => __('مكتبة PDF لم تُحمّل.'),
        'pdfSelectTwo'    => __('اختر ملفين على الأقل.'),
        'pdfBadPages'     => __('صيغة أرقام الصفحات غير صحيحة. مثال: 1-3,5'),
        'pdfPagesOut'     => __('أرقام الصفحات خارج نطاق الملف.'),
        'pdfAllDeleted'   => __('لا يمكن حذف جميع الصفحات.'),
        'pdfOneImage'     => __('اختر صورة واحدة على الأقل.'),
        'pdfPagesCount'   => __(':n صفحة'),
        'pdfNoMatch'      => __('لا توجد صفحات مطابقة.'),
        'uploading'       => __('جاري الرفع...'),
        'savingShare'     => __('تم الرفع، جاري الحفظ وإنشاء الرابط...'),
        'processingServer'=> __('تم الرفع، جاري المعالجة على الخادم...'),
        'etaPrefix'       => __('باقي ~ :time'),
        'secShort'        => __('ث_وحدة'),
        'minShort'        => __('د_وحدة'),
        'tooBigClient'    => __('حجم الملف (:size) يتجاوز الحد المسموح (:max).'),
        'chooseFileShare' => __('اختر ملفاً للمشاركة أولاً.'),
        'maxFilesLimit'   => __('الحد الأقصى 20 ملفاً في المرة الواحدة.'),
        'moveUp'          => __('تحريك للأعلى'),
        'moveDown'        => __('تحريك للأسفل'),
        'removeFile'      => __('إزالة الملف'),
        'invalidDim'      => __('أدخل قيمة رقمية أكبر من الصفر.'),
        'invalidDimForm'  => __('أدخل أبعاداً صحيحة أكبر من الصفر.'),
        'textPlaceholder' => __('اكتب النص للمعاينة...'),
    ];
?>
<!doctype html>
<html lang="<?php echo e($locale); ?>" dir="<?php echo e($isRtl ? 'rtl' : 'ltr'); ?>" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php
        $metaTitle = trim($__env->yieldContent('title', config('app.name'))) . ' · ' . config('app.name');
        $metaDesc  = __('منصة متخصصة لتعديل الملفات، تحويل الصيغ، وتصغير الحجم — مع إمكانية تحويل أي ملف إلى رابط مباشر أو رمز QR للمشاركة السريعة.');
    ?>
    <title><?php echo e($metaTitle); ?></title>
    <meta name="description" content="<?php echo e($metaDesc); ?>">
    <meta name="theme-color" content="#0d6e66">
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">

    <!-- أيقونات المتصفح ونتائج بحث قوقل -->
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?php echo e(asset('assets/img/icon-48.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('assets/img/icon-192.png')); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('assets/img/icon.svg')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('assets/img/apple-touch-icon.png')); ?>">

    <!-- Open Graph / Twitter للمشاركات وقوقل -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo e(config('app.name')); ?>">
    <meta property="og:title" content="<?php echo e($metaTitle); ?>">
    <meta property="og:description" content="<?php echo e($metaDesc); ?>">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:image" content="<?php echo e(asset('assets/img/og-cover.png')); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($metaDesc); ?>">
    <meta name="twitter:image" content="<?php echo e(asset('assets/img/og-cover.png')); ?>">

    
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebSite","name":"Ju File","url":"https://jufile.win"}
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <?php if($isRtl): ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/style.css?v=11')); ?>" rel="stylesheet">
    <script>
        window.JU_I18N = <?php echo json_encode($jsI18N, JSON_UNESCAPED_UNICODE, 512) ?>;
        (() => {
            const t = localStorage.getItem('ju_theme') === 'light' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo e(route('home')); ?>"><i class="bi bi-folder2-open text-accent"></i> <?php echo e(config('app.name')); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="<?php echo e(__('القائمة')); ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navKey => $navCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(($activePage ?? '') === $navKey ? 'active' : ''); ?>" href="<?php echo e(route('tools.' . $navKey)); ?>">
                            <i class="bi <?php echo e($navCat['icon']); ?>"></i> <?php echo e($navCat['title']); ?>

                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-accent btn-sm" href="<?php echo e(route('shares.create')); ?>"><i class="bi bi-qr-code"></i> <?php echo e(__('مشاركة ملف')); ?></a>
                <?php if(auth()->guard()->check()): ?>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo e(auth()->user()->name); ?>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo e(route('dashboard')); ?>"><i class="bi bi-grid"></i> <?php echo e(__('ملفاتي')); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo e(route('account')); ?>"><i class="bi bi-gear"></i> <?php echo e(__('الحساب')); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i> <?php echo e(__('تسجيل الخروج')); ?></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-light btn-sm <?php echo e(($activePage ?? '') === 'login' ? 'active' : ''); ?>" href="<?php echo e(route('login')); ?>"><?php echo e(__('دخول')); ?></a>
                    <a class="btn btn-outline-dark btn-sm" href="<?php echo e(route('register')); ?>"><?php echo e(__('حساب جديد')); ?></a>
                <?php endif; ?>
                <a href="<?php echo e(route('locale.switch', $nextLocale)); ?>"
                   class="btn btn-light btn-sm lang-toggle"
                   title="<?php echo e($isRtl ? 'Switch to English' : 'التبديل إلى العربية'); ?>"
                   aria-label="<?php echo e($isRtl ? 'Switch to English' : 'التبديل إلى العربية'); ?>">
                    <i class="bi bi-translate text-accent"></i>
                    <span><?php echo e($isRtl ? 'EN' : 'عربي'); ?></span>
                </a>
                <button type="button" id="themeToggle" class="btn btn-light btn-sm theme-toggle" title="<?php echo e(__('تبديل المظهر (داكن / فاتح)')); ?>" aria-label="<?php echo e(__('تبديل المظهر (داكن / فاتح)')); ?>">
                    <i class="bi bi-sun"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <?php $__currentLoopData = ['success', 'danger', 'warning', 'info']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flashType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(session($flashType)): ?>
            <div class="alert alert-<?php echo e($flashType); ?> alert-dismissible fade show" role="alert">
                <?php echo e(session($flashType)); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo e(__('إغلاق')); ?>"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php echo $__env->yieldContent('content'); ?>
</main>

<footer class="border-top py-4 mt-5 bg-white">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
            <div><i class="bi bi-folder2-open text-accent"></i> <strong><?php echo e(config('app.name')); ?></strong> — <?php echo e(__('كل أدوات ملفاتك في مكان واحد')); ?></div>
            <nav class="d-flex gap-3">
                <?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navKey => $footerCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a class="link-secondary text-decoration-none" href="<?php echo e(route('tools.' . $navKey)); ?>"><?php echo e($footerCat['title']); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <a class="link-secondary text-decoration-none" href="<?php echo e(route('shares.create')); ?>"><?php echo e(__('المشاركة')); ?></a>
            </nav>
            <div>&copy; <?php echo e(date('Y')); ?></div>
        </div>
        <div class="border-top mt-3 pt-3 text-center small text-secondary" dir="ltr">
            <i class="bi bi-mortarboard text-accent me-1"></i>
            This website was created by <strong class="text-body">Dr. Malkadi</strong> students —
            The students: <span class="text-accent fw-semibold">Majid Hamitai</span> &amp; <span class="text-accent fw-semibold">Majid Kamli</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(asset('assets/vendor/qrcode.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/app.js?v=7')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/layouts/app.blade.php ENDPATH**/ ?>