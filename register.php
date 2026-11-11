<?php
/** إنشاء حساب جديد. */
require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if (is_post()) {
    verify_csrf();
    $old['name']  = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password     = (string) ($_POST['password'] ?? '');
    $confirm      = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 80) {
        $errors['name'] = 'الاسم يجب أن يكون بين 2 و 80 حرفاً.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 190) {
        $errors['email'] = 'أدخل بريداً إلكترونياً صحيحاً.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
    } elseif ($password !== $confirm) {
        $errors['password_confirm'] = 'كلمتا المرور غير متطابقتين.';
    }
    if (!$errors) {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'هذا البريد مسجّل مسبقاً، جرّب تسجيل الدخول.';
        }
    }
    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);
        login_user((int) db()->lastInsertId());
        flash('success', 'أهلاً ' . $old['name'] . '! تم إنشاء حسابك بنجاح.');
        redirect('dashboard.php');
    }
}

$pageTitle = 'حساب جديد';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1">إنشاء حساب</h1>
    <p class="text-secondary small mb-4">احفظ روابطك، أدِرها، واحصل على روابط بلا انتهاء.</p>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php
        $inputs = [
            ['name', 'الاسم', 'text', 'name'],
            ['email', 'البريد الإلكتروني', 'email', 'email'],
            ['password', 'كلمة المرور', 'password', 'new-password'],
            ['password_confirm', 'تأكيد كلمة المرور', 'password', 'new-password'],
        ];
        foreach ($inputs as [$n, $label, $type, $ac]): ?>
            <div class="mb-3">
                <label class="form-label" for="<?= $n ?>"><?= $label ?></label>
                <input class="form-control <?= isset($errors[$n]) ? 'is-invalid' : '' ?>" type="<?= $type ?>" id="<?= $n ?>" name="<?= $n ?>"
                       autocomplete="<?= $ac ?>" value="<?= $type === 'password' ? '' : e($old[$n]) ?>" required>
                <?php if (isset($errors[$n])): ?><div class="invalid-feedback"><?= e($errors[$n]) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-accent w-100">إنشاء الحساب</button>
    </form>
    <p class="small text-center mt-3 mb-0">لديك حساب؟ <a href="login.php">سجّل الدخول</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

