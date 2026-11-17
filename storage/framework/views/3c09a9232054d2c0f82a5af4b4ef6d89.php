<?php
    use App\Services\ToolCatalog;
    $navCategories = ToolCatalog::categories();
?>
<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?> · <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/style.css?v=3')); ?>" rel="stylesheet">
    <script>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="القائمة">
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
                <a class="btn btn-accent btn-sm" href="<?php echo e(route('shares.create')); ?>"><i class="bi bi-qr-code"></i> مشاركة ملف</a>
                <?php if(auth()->guard()->check()): ?>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo e(auth()->user()->name); ?>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo e(route('dashboard')); ?>"><i class="bi bi-grid"></i> ملفاتي</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(route('account')); ?>"><i class="bi bi-gear"></i> الحساب</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-light btn-sm <?php echo e(($activePage ?? '') === 'login' ? 'active' : ''); ?>" href="<?php echo e(route('login')); ?>">دخول</a>
                    <a class="btn btn-outline-dark btn-sm" href="<?php echo e(route('register')); ?>">حساب جديد</a>
                <?php endif; ?>
                <button type="button" id="themeToggle" class="btn btn-light btn-sm theme-toggle" title="تبديل المظهر (داكن / فاتح)" aria-label="تبديل المظهر">
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

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php echo $__env->yieldContent('content'); ?>
</main>

<footer class="border-top py-4 mt-5 bg-white">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
        <div><i class="bi bi-folder2-open text-accent"></i> <strong><?php echo e(config('app.name')); ?></strong> — كل أدوات ملفاتك في مكان واحد</div>
        <nav class="d-flex gap-3">
            <?php $__currentLoopData = $navCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navKey => $footerCat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="link-secondary text-decoration-none" href="<?php echo e(route('tools.' . $navKey)); ?>"><?php echo e($footerCat['title']); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <a class="link-secondary text-decoration-none" href="<?php echo e(route('shares.create')); ?>">المشاركة</a>
        </nav>
        <div>&copy; <?php echo e(date('Y')); ?></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(asset('assets/vendor/qrcode.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/app.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>

<?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/layouts/app.blade.php ENDPATH**/ ?>