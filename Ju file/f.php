<?php
/** صفحة الملف المشارك العامة: معلومات + معاينة + تنزيل + QR. */
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/components.php';

$token = (string) ($_GET['t'] ?? '');
$share = find_share($token);

if ($share && !$share['expired'] && is_post()) {
    verify_csrf();
    if (password_verify((string) ($_POST['password'] ?? ''), (string) $share['password_hash'])) {
        $_SESSION['unlocked'][$share['token']] = true;
        flash('success', 'تم فتح الملف.');
    } else {
        usleep(400000); // إبطاء محاولات التخمين
        flash('danger', 'كلمة المرور غير صحيحة.');
    }
    redirect('f.php?t=' . $share['token']);
}

$pageTitle = $share ? ($share['title'] ?: $share['original_name']) : 'ملف غير موجود';
require __DIR__ . '/includes/header.php';

if (!$share || $share['expired']): ?>
    <div class="panel text-center py-5 mx-auto" style="max-width:520px">
        <i class="bi bi-file-earmark-x display-4 text-secondary"></i>
        <h1 class="h4 fw-bold mt-3"><?= $share ? 'انتهت صلاحية هذا الرابط' : 'الملف غير موجود' ?></h1>
        <p class="text-secondary">ربما حُذف الملف أو انتهت مدته.</p>
        <a class="btn btn-accent" href="share.php">شارك ملفاً جديداً</a>
    </div>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
endif;

$unlocked = share_unlocked($share);
$isOwner  = current_user() && (int) current_user()['id'] === (int) $share['user_id'];
$dl       = 'download.php?t=' . e($share['token']);
$mime     = $share['mime'];
$previewable = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) ? 'img'
    : (str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') ? 'audio' : null));
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-7">
        <div class="panel">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="file-icon"><i class="bi <?= e(category_icon($share['category'])) ?>"></i></div>
                <div class="min-w-0">
                    <h1 class="h5 fw-bold mb-0 text-truncate"><?= e($share['title'] ?: $share['original_name']) ?></h1>
                    <div class="small text-secondary text-truncate"><?= e($share['original_name']) ?></div>
                </div>
            </div>

            <?php if (!$unlocked): ?>
                <form method="post" class="border rounded-3 p-3 bg-light">
                    <?= csrf_field() ?>
                    <label class="form-label fw-bold" for="password"><i class="bi bi-lock"></i> هذا الملف محمي بكلمة مرور</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required autofocus>
                        <button class="btn btn-accent">فتح</button>
                    </div>
                </form>
            <?php else: ?>
                <?php if ($previewable === 'img'): ?>
                    <img class="preview-media mb-3" src="<?= $dl ?>&amp;inline=1" alt="<?= e($share['original_name']) ?>">
                <?php elseif ($previewable === 'video'): ?>
                    <video class="preview-media mb-3" src="<?= $dl ?>&amp;inline=1" controls preload="metadata"></video>
                <?php elseif ($previewable === 'audio'): ?>
                    <audio class="w-100 mb-3" src="<?= $dl ?>&amp;inline=1" controls preload="metadata"></audio>
                <?php endif; ?>
                <a class="btn btn-accent btn-lg w-100" href="<?= $dl ?>"><i class="bi bi-download"></i> تنزيل (<?= e(format_bytes((int) $share['size'])) ?>)</a>
            <?php endif; ?>

            <dl class="meta-list mt-4 mb-0">
                <div><dt>النوع</dt><dd><?= e(category_label($share['category'])) ?> · <span dir="ltr"><?= e($mime) ?></span></dd></div>
                <div><dt>الحجم</dt><dd><?= e(format_bytes((int) $share['size'])) ?></dd></div>
                <div><dt>التنزيلات</dt><dd><?= (int) $share['downloads'] ?></dd></div>
                <div><dt>تاريخ الرفع</dt><dd dir="ltr"><?= e($share['created_at']) ?></dd></div>
                <div><dt>ينتهي</dt><dd dir="ltr"><?= e($share['expires_at'] ?? 'بدون انتهاء') ?></dd></div>
            </dl>

            <?php if ($isOwner): ?>
                <div class="d-flex gap-2 mt-3">
                    <a class="btn btn-light btn-sm" href="share_edit.php?id=<?= (int) $share['id'] ?>"><i class="bi bi-pencil"></i> تعديل</a>
                    <a class="btn btn-light btn-sm" href="dashboard.php"><i class="bi bi-grid"></i> ملفاتي</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h6 fw-bold mb-1"><i class="bi bi-share"></i> شارك هذا الملف</h2>
            <?php if (!empty($share['external_url'])): ?>
                <p class="small text-secondary mb-3"><i class="bi bi-globe2 text-accent"></i> رابط سحابي عام يعمل من أي جوال أو جهاز مباشرة</p>
            <?php endif; ?>
            <?= share_box(share_public_url($share)) ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

