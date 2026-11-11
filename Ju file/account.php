<?php
/** إعدادات الحساب: تعديل الاسم، تغيير كلمة المرور، حذف الحساب. */
require __DIR__ . '/includes/init.php';

$user = require_login();
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['profile'] = 'الاسم يجب أن يكون بين 2 و 80 حرفاً.';
        } else {
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $user['id']]);
            flash('success', 'تم تحديث الاسم.');
            redirect('account.php');
        }
    }

    if ($action === 'password' || $action === 'delete') {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $currentOk = password_verify((string) ($_POST['current_password'] ?? ''), (string) $stmt->fetchColumn());

        if ($action === 'password') {
            $new = (string) ($_POST['new_password'] ?? '');
            if (!$currentOk) {
                $errors['password'] = 'كلمة المرور الحالية غير صحيحة.';
            } elseif (strlen($new) < 8 || strlen($new) > 72) {
                $errors['password'] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
            } elseif ($new !== ($_POST['new_password_confirm'] ?? '')) {
                $errors['password'] = 'كلمتا المرور غير متطابقتين.';
            } else {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
                session_regenerate_id(true);
                flash('success', 'تم تغيير كلمة المرور.');
                redirect('account.php');
            }
        } else { // delete
            if (!$currentOk) {
                $errors['delete'] = 'كلمة المرور غير صحيحة.';
            } else {
                // حذف ملفات المستخدم من القرص، ثم الحساب (الروابط تُحذف تلقائياً بـ ON DELETE CASCADE)
                $stmt = db()->prepare('SELECT stored_name FROM shares WHERE user_id = ?');
                $stmt->execute([$user['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $stored) {
                    @unlink(STORAGE_DIR . '/' . $stored);
                }
                db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
                $_SESSION = [];
                session_regenerate_id(true);
                flash('success', 'تم حذف حسابك وجميع ملفاتك.');
                redirect('index.php');
            }
        }
    }
}

$pageTitle = 'الحساب';
require __DIR__ . '/includes/header.php';

function form_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="alert alert-danger py-2 small">' . e($errors[$key]) . '</div>' : '';
}
?>
<div class="mx-auto" style="max-width:620px">
    <h1 class="h3 fw-bold mb-1">إعدادات الحساب</h1>
    <p class="text-secondary mb-4"><?= e($user['email']) ?> · عضو منذ <span dir="ltr"><?= e(substr($user['created_at'], 0, 10)) ?></span></p>

    <div class="panel mb-4">
        <h2 class="h6 fw-bold mb-3">الملف الشخصي</h2>
        <?= form_error($errors, 'profile') ?>
        <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <input class="form-control" name="name" value="<?= e($user['name']) ?>" maxlength="80" required aria-label="الاسم">
            <button class="btn btn-accent text-nowrap">حفظ الاسم</button>
        </form>
    </div>

    <div class="panel mb-4">
        <h2 class="h6 fw-bold mb-3">تغيير كلمة المرور</h2>
        <?= form_error($errors, 'password') ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <input class="form-control mb-2" type="password" name="current_password" placeholder="كلمة المرور الحالية" autocomplete="current-password" required>
            <input class="form-control mb-2" type="password" name="new_password" placeholder="كلمة المرور الجديدة" autocomplete="new-password" required>
            <input class="form-control mb-3" type="password" name="new_password_confirm" placeholder="تأكيد كلمة المرور الجديدة" autocomplete="new-password" required>
            <button class="btn btn-dark">تغيير كلمة المرور</button>
        </form>
    </div>

    <div class="panel border-danger-subtle">
        <h2 class="h6 fw-bold text-danger mb-2">حذف الحساب</h2>
        <p class="small text-secondary">سيتم حذف حسابك وكل ملفاتك وروابطك نهائياً.</p>
        <?= form_error($errors, 'delete') ?>
        <form method="post" class="d-flex gap-2" data-confirm="هل أنت متأكد؟ لا يمكن التراجع عن حذف الحساب.">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <input class="form-control" type="password" name="current_password" placeholder="أدخل كلمة المرور للتأكيد" autocomplete="current-password" required>
            <button class="btn btn-outline-danger text-nowrap">حذف حسابي</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

