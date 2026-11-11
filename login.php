<?php
/** تسجيل الدخول باستخدام الجلسات. */
require __DIR__ . '/includes/init.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);
if (current_user()) {
    redirect($next);
}

$error = null;
$email = '';

if (is_post()) {
    verify_csrf();
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'أدخل البريد الإلكتروني وكلمة المرور.';
    } else {
        $stmt = db()->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_user((int) $user['id']);
            flash('success', 'مرحباً بعودتك، ' . $user['name'] . '!');
            redirect($next);
        }
        usleep(300000);
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'; // رسالة عامة لا تكشف وجود الحساب
    }
}

$pageTitle = 'تسجيل الدخول';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1">تسجيل الدخول</h1>
    <p class="text-secondary small mb-4">ادخل لإدارة ملفاتك وروابطك.</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="mb-3">
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">كلمة المرور</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-accent w-100">دخول</button>
    </form>
    <p class="small text-center mt-3 mb-0">ليس لديك حساب؟ <a href="register.php">أنشئ حساباً</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

