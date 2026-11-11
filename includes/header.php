<?php
/**
 * رأس الصفحة المشترك (مكوّن قابل لإعادة الاستخدام).
 * متغيرات اختيارية قبل التضمين: $pageTitle, $activePage
 */
$pageTitle  = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? '';
$user       = current_user();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-folder2-open text-accent"></i> <?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="القائمة">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach (tool_categories() as $navKey => $navCat): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === $navKey ? 'active' : '' ?>" href="<?= e($navCat['page']) ?>">
                            <i class="bi <?= e($navCat['icon']) ?>"></i> <?= e($navCat['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-accent btn-sm" href="share.php"><i class="bi bi-qr-code"></i> مشاركة ملف</a>
                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= e($user['name']) ?></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-grid"></i> ملفاتي</a></li>
                            <li><a class="dropdown-item" href="account.php"><i class="bi bi-gear"></i> الحساب</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="logout.php"><?= csrf_field() ?>
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-light btn-sm <?= $activePage === 'login' ? 'active' : '' ?>" href="login.php">دخول</a>
                    <a class="btn btn-outline-dark btn-sm" href="register.php">حساب جديد</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    <?php endforeach; ?>

