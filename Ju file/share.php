<?php
/**
 * مشاركة أي ملف: يرفع الملف ويُنشئ رابطاً قصيراً + QR.
 * يعمل كنموذج عادي، أو كواجهة JSON عند إرسال ajax=1 (من لوحة نتيجة الأدوات).
 */
require __DIR__ . '/includes/init.php';

$user = current_user();
$expiryOptions = expiry_options((bool) $user);
$errors = [];
$old = ['title' => '', 'expiry' => '24'];

if (is_post()) {
    $ajax = !empty($_POST['ajax']);
    verify_csrf($ajax);
    cleanup_expired_shares();

    $old['title']  = trim((string) ($_POST['title'] ?? ''));
    $old['expiry'] = (string) ($_POST['expiry'] ?? '24');
    $password      = (string) ($_POST['password'] ?? '');

    // ---- التحقق من المدخلات على الخادم ----
    if (mb_strlen($old['title']) > 150) {
        $errors[] = 'العنوان يجب ألا يتجاوز 150 حرفاً.';
    }
    if (!array_key_exists($old['expiry'], $expiryOptions)) {
        $errors[] = 'مدة الصلاحية غير صالحة' . ($user ? '.' : ' (سجّل دخولك للمدد الأطول).');
    }
    if ($password !== '' && (mb_strlen($password) < 4 || mb_strlen($password) > 72)) {
        $errors[] = 'كلمة مرور الرابط يجب أن تكون بين 4 و 72 حرفاً.';
    }
    $files = uploaded_files('file');
    if (count($files) !== 1) {
        $errors[] = 'اختر ملفاً واحداً للمشاركة.';
    } elseif ($err = upload_error($files[0])) {
        $errors[] = $err;
    }

    if (!$errors) {
        $file = $files[0];
        if (!is_dir(STORAGE_DIR)) {
            mkdir(STORAGE_DIR, 0750, true);
        }
        $stored = bin2hex(random_bytes(16)); // اسم عشوائي بدون امتداد: لا يمكن تنفيذه أو تخمينه
        if (!move_uploaded_file($file['tmp_name'], STORAGE_DIR . '/' . $stored)) {
            $errors[] = 'تعذّر حفظ الملف على الخادم.';
        }
    }

    if (!$errors) {
        $storedPath  = STORAGE_DIR . '/' . $stored;
        $mime        = detect_mime($storedPath);
        $externalUrl = upload_to_external_host($storedPath, $file['name'], $mime);
        $token       = bin2hex(random_bytes(8));
        $hours       = $old['expiry'] === 'never' ? 0 : (int) $old['expiry'];
        $params      = [
            $user['id'] ?? null, $token, $old['title'] !== '' ? $old['title'] : null, clean_filename($file['name']),
            $stored, $externalUrl, $mime, $file['size'], category_of_mime($mime),
            $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
        ];
        $expSql = 'NULL';
        if ($hours) {
            $expSql = 'DATE_ADD(NOW(), INTERVAL ? HOUR)';
            $params[] = $hours;
        }
        $stmt = db()->prepare("INSERT INTO shares (user_id, token, title, original_name, stored_name, external_url, mime, size, category, password_hash, expires_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, $expSql)");
        $stmt->execute($params);
        log_operation('share', 'link', $file['name']);

        $publicUrl = $externalUrl ?: app_url('f.php?t=' . $token);
        if ($ajax) {
            json_response(['url' => $publicUrl, 'token' => $token]);
        }
        flash('success', 'تم رفع الملف وإنشاء رابط المشاركة العام ورمز QR بنجاح!');
        redirect('f.php?t=' . $token);
    }

    if ($ajax) {
        json_response(['error' => implode("\n", $errors)], 422);
    }
}

// آخر مشاركات المستخدم (عرض بيانات ديناميكي)
$recent = [];
if ($user) {
    $stmt = db()->prepare('SELECT token, title, original_name, category, created_at FROM shares
                           WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $recent = $stmt->fetchAll();
}

$pageTitle = 'مشاركة ملف';
$activePage = 'share';
require __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
    <h1 class="h3 fw-bold"><i class="bi bi-qr-code text-accent"></i> حوّل أي ملف إلى رابط أو QR</h1>
    <p class="text-secondary">ارفع صورة، فيديو، مستند أو أي ملف — واحصل على رابط قصير ورمز QR لمشاركته فوراً.</p>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="panel">
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <label class="dropzone mb-3" data-simple-dropzone>
                    <input type="file" name="file" hidden required>
                    <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                    <span class="fw-bold mt-2" data-file-label>اسحب الملف هنا أو اضغط للاختيار</span>
                    <small class="text-secondary">أي نوع ملف — الحد الأقصى <?= e(format_bytes(upload_limit_bytes())) ?></small>
                </label>
                <div class="mb-3">
                    <label class="form-label" for="title">عنوان (اختياري)</label>
                    <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($old['title']) ?>" placeholder="مثال: صور الرحلة">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="expiry">صلاحية الرابط</label>
                        <select class="form-select" id="expiry" name="expiry">
                            <?php foreach ($expiryOptions as $k => $label): ?>
                                <option value="<?= e($k) ?>" <?= (string) $k === $old['expiry'] ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password">كلمة مرور (اختياري)</label>
                        <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" placeholder="لحماية الملف">
                    </div>
                </div>
                <button class="btn btn-accent w-100 btn-lg"><i class="bi bi-link-45deg"></i> أنشئ الرابط و QR</button>
                <?php if (!$user): ?>
                    <p class="small text-secondary mt-3 mb-0"><i class="bi bi-info-circle"></i> <a href="login.php?next=/share.php">سجّل دخولك</a> لروابط بلا انتهاء وإدارة ملفاتك من لوحتك.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($recent): ?>
        <div class="col-lg-4">
            <div class="panel">
                <h2 class="h6 fw-bold mb-3">آخر مشاركاتك</h2>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recent as $r): ?>
                        <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <i class="bi <?= e(category_icon($r['category'])) ?> text-accent"></i>
                            <a class="text-truncate flex-grow-1 text-decoration-none" href="f.php?t=<?= e($r['token']) ?>"><?= e($r['title'] ?: $r['original_name']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="small d-inline-block mt-3" href="dashboard.php">كل ملفاتي ←</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

