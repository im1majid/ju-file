<?php
/** تعديل ملف مشارك: العنوان، مدة الصلاحية، كلمة المرور (UPDATE). */
require __DIR__ . '/includes/init.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM shares WHERE id = ? AND user_id = ?'); // المالك فقط
$stmt->execute([$id, $user['id']]);
$share = $stmt->fetch();
if (!$share) {
    flash('danger', 'الملف غير موجود.');
    redirect('dashboard.php');
}

$expiryOptions = ['keep' => 'بدون تغيير'] + expiry_options(true);
$errors = [];
$title = $share['title'] ?? '';

if (is_post()) {
    verify_csrf();
    $title   = trim((string) ($_POST['title'] ?? ''));
    $expiry  = (string) ($_POST['expiry'] ?? 'keep');
    $pwMode  = (string) ($_POST['password_mode'] ?? 'keep');
    $newPass = (string) ($_POST['password'] ?? '');

    if (mb_strlen($title) > 150) {
        $errors[] = 'العنوان يجب ألا يتجاوز 150 حرفاً.';
    }
    if (!array_key_exists($expiry, $expiryOptions)) {
        $errors[] = 'مدة الصلاحية غير صالحة.';
    }
    if (!in_array($pwMode, ['keep', 'set', 'remove'], true)) {
        $errors[] = 'خيار كلمة المرور غير صالح.';
    } elseif ($pwMode === 'set' && (mb_strlen($newPass) < 4 || mb_strlen($newPass) > 72)) {
        $errors[] = 'كلمة المرور الجديدة يجب أن تكون بين 4 و 72 حرفاً.';
    }

    if (!$errors) {
        $sets = ['title = ?'];
        $params = [$title !== '' ? $title : null];
        if ($expiry === 'never') {
            $sets[] = 'expires_at = NULL';
        } elseif ($expiry !== 'keep') {
            $sets[] = 'expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR)';
            $params[] = (int) $expiry;
        }
        if ($pwMode === 'set') {
            $sets[] = 'password_hash = ?';
            $params[] = password_hash($newPass, PASSWORD_DEFAULT);
        } elseif ($pwMode === 'remove') {
            $sets[] = 'password_hash = NULL';
        }
        array_push($params, $share['id'], $user['id']);
        db()->prepare('UPDATE shares SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?')->execute($params);
        flash('success', 'تم حفظ التعديلات.');
        redirect('dashboard.php');
    }
}

$pageTitle = 'تعديل الملف';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card panel mx-auto" style="max-width:560px">
    <nav class="small mb-2"><a href="dashboard.php" class="link-secondary text-decoration-none">ملفاتي</a> <span class="text-secondary">/ تعديل</span></nav>
    <h1 class="h5 fw-bold mb-1 text-truncate"><?= e($share['original_name']) ?></h1>
    <p class="small text-secondary mb-4">ينتهي حالياً: <span dir="ltr"><?= e($share['expires_at'] ?? 'بدون انتهاء') ?></span></p>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="title">العنوان</label>
            <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($title) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="expiry">تمديد / تغيير الصلاحية (من الآن)</label>
            <select class="form-select" id="expiry" name="expiry">
                <?php foreach ($expiryOptions as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_mode">كلمة المرور <?= $share['password_hash'] ? '<span class="badge text-bg-light"><i class="bi bi-lock"></i> مفعّلة</span>' : '' ?></label>
            <select class="form-select mb-2" id="password_mode" name="password_mode" data-toggle-target="#newPassWrap" data-toggle-value="set">
                <option value="keep">بدون تغيير</option>
                <option value="set"><?= $share['password_hash'] ? 'تغيير كلمة المرور' : 'إضافة كلمة مرور' ?></option>
                <?php if ($share['password_hash']): ?><option value="remove">إزالة كلمة المرور</option><?php endif; ?>
            </select>
            <div id="newPassWrap" class="d-none">
                <input class="form-control" type="password" name="password" autocomplete="new-password" placeholder="كلمة المرور الجديدة">
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-accent flex-grow-1">حفظ</button>
            <a class="btn btn-light" href="dashboard.php">إلغاء</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

