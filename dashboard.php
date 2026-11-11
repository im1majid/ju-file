<?php
/** لوحة المستخدم: ملفاته المشاركة (بحث/فلترة/حذف) + سجل العمليات. */
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/components.php';

$user = require_login();

// ---- العمليات (حذف) ----
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $stmt = db()->prepare('SELECT stored_name FROM shares WHERE id = ? AND user_id = ?'); // التحقق من الملكية
        $stmt->execute([(int) ($_POST['id'] ?? 0), $user['id']]);
        if ($row = $stmt->fetch()) {
            db()->prepare('DELETE FROM shares WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], $user['id']]);
            @unlink(STORAGE_DIR . '/' . $row['stored_name']);
            flash('success', 'تم حذف الملف ورابطه.');
        } else {
            flash('danger', 'الملف غير موجود.');
        }
    } elseif ($action === 'clear_history') {
        db()->prepare('DELETE FROM operations WHERE user_id = ?')->execute([$user['id']]);
        flash('success', 'تم مسح سجل العمليات.');
    }
    redirect('dashboard.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'category', 'status', 'sort']))));
}

// ---- الفلترة (استعلام مبني بقيم مُجهّزة وقوائم بيضاء) ----
$q        = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$status   = (string) ($_GET['status'] ?? 'active');
$sort     = (string) ($_GET['sort'] ?? 'newest');

$categories = ['image' => 'صور', 'video' => 'فيديو', 'audio' => 'صوت', 'document' => 'مستندات', 'other' => 'أخرى'];
$sorts = ['newest' => 's.id DESC', 'oldest' => 's.id ASC', 'downloads' => 's.downloads DESC', 'size' => 's.size DESC'];

$where = ['s.user_id = ?'];
$params = [$user['id']];
if ($q !== '') {
    $where[] = '(s.title LIKE ? OR s.original_name LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($params, $like, $like);
}
if (isset($categories[$category])) {
    $where[] = 's.category = ?';
    $params[] = $category;
}
if ($status === 'active') {
    $where[] = '(s.expires_at IS NULL OR s.expires_at > NOW())';
} elseif ($status === 'expired') {
    $where[] = 's.expires_at <= NOW()';
}
$orderBy = $sorts[$sort] ?? $sorts['newest'];

$stmt = db()->prepare('SELECT s.*, (s.expires_at IS NOT NULL AND s.expires_at < NOW()) AS expired FROM shares s WHERE '
    . implode(' AND ', $where) . " ORDER BY $orderBy LIMIT 200");
$stmt->execute($params);
$shares = $stmt->fetchAll();

// ---- إحصائيات ----
$stmt = db()->prepare('SELECT COUNT(*) AS files, COALESCE(SUM(downloads), 0) AS downloads, COALESCE(SUM(size), 0) AS size
                       FROM shares WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())');
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

$stmt = db()->prepare('SELECT COUNT(*) FROM operations WHERE user_id = ?');
$stmt->execute([$user['id']]);
$opsCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT category, tool, file_name, created_at FROM operations WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$stmt->execute([$user['id']]);
$history = $stmt->fetchAll();

/** اسم الأداة المقروء من تعريف الأدوات. */
function tool_title(string $cat, string $tool): string
{
    if ($cat === 'share') {
        return 'مشاركة برابط';
    }
    $c = tool_categories()[$cat] ?? null;
    return $c ? $c['title'] . ' · ' . ($c['tools'][$tool]['title'] ?? $tool) : $tool;
}

$pageTitle = 'ملفاتي';
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">ملفاتي</h1>
        <p class="text-secondary mb-0">أهلاً <?= e($user['name']) ?>، هنا روابطك وسجلّك.</p>
    </div>
    <a class="btn btn-accent" href="share.php"><i class="bi bi-plus-lg"></i> مشاركة ملف</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['bi-files', 'ملفات نشطة', (int) $stats['files']],
        ['bi-download', 'مرات التنزيل', (int) $stats['downloads']],
        ['bi-hdd', 'المساحة المستخدمة', format_bytes((int) $stats['size'])],
        ['bi-lightning', 'عمليات منفذة', $opsCount],
    ] as [$icon, $label, $value]): ?>
        <div class="col-6 col-lg-3">
            <div class="panel stat"><i class="bi <?= $icon ?>"></i><div><div class="stat-value"><?= e($value) ?></div><div class="small text-secondary"><?= $label ?></div></div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="panel mb-4">
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="ابحث بالاسم أو العنوان..."></div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="category">
                <option value="">كل الأنواع</option>
                <?php foreach ($categories as $k => $label): ?><option value="<?= $k ?>" <?= $category === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="status">
                <?php foreach (['active' => 'النشطة', 'expired' => 'المنتهية', 'all' => 'الكل'] as $k => $label): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="sort">
                <?php foreach (['newest' => 'الأحدث', 'oldest' => 'الأقدم', 'downloads' => 'الأكثر تنزيلاً', 'size' => 'الأكبر حجماً'] as $k => $label): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2"><button class="btn btn-dark w-100"><i class="bi bi-funnel"></i> تصفية</button></div>
    </form>

    <?php if (!$shares): ?>
        <div class="text-center text-secondary py-5"><i class="bi bi-inbox display-6"></i><p class="mt-2 mb-0">لا توجد ملفات مطابقة.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>الملف</th><th>الحجم</th><th>التنزيلات</th><th>الصلاحية</th><th class="text-end">إجراءات</th></tr></thead>
                <tbody>
                <?php foreach ($shares as $s): $link = share_public_url($s); ?>
                    <tr>
                        <td class="min-w-0">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= e(category_icon($s['category'])) ?> text-accent fs-5"></i>
                                <div class="min-w-0">
                                    <a class="fw-bold text-decoration-none d-block text-truncate" style="max-width:280px" href="f.php?t=<?= e($s['token']) ?>"><?= e($s['title'] ?: $s['original_name']) ?></a>
                                    <small class="text-secondary"><?= e(category_label($s['category'])) ?><?= $s['password_hash'] ? ' · <i class="bi bi-lock"></i> محمي' : '' ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-nowrap"><?= e(format_bytes((int) $s['size'])) ?></td>
                        <td><?= (int) $s['downloads'] ?></td>
                        <td class="text-nowrap small">
                            <?php if ($s['expired']): ?><span class="badge text-bg-secondary">منتهي</span>
                            <?php elseif (!$s['expires_at']): ?><span class="badge text-bg-success">دائم</span>
                            <?php else: ?><span dir="ltr"><?= e($s['expires_at']) ?></span><?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-light btn-sm" type="button" data-copy="<?= e($link) ?>" title="نسخ الرابط"><i class="bi bi-clipboard"></i></button>
                            <a class="btn btn-light btn-sm" href="f.php?t=<?= e($s['token']) ?>" title="الرابط و QR"><i class="bi bi-qr-code"></i></a>
                            <a class="btn btn-light btn-sm" href="share_edit.php?id=<?= (int) $s['id'] ?>" title="تعديل"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="حذف هذا الملف ورابطه نهائياً؟">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <button class="btn btn-light btn-sm text-danger" title="حذف"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-clock-history"></i> آخر العمليات</h2>
        <?php if ($history): ?>
            <form method="post" data-confirm="مسح كل سجل العمليات؟"><?= csrf_field() ?><input type="hidden" name="action" value="clear_history">
                <button class="btn btn-link btn-sm text-danger p-0">مسح السجل</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if (!$history): ?>
        <p class="text-secondary small mb-0">لم تستخدم أي أداة بعد. <a href="index.php">ابدأ الآن</a></p>
    <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($history as $h): ?>
                <li class="d-flex justify-content-between gap-2 py-2 border-bottom small">
                    <span><strong><?= e(tool_title($h['category'], $h['tool'])) ?></strong> <span class="text-secondary"><?= e($h['file_name'] ?? '') ?></span></span>
                    <span class="text-secondary text-nowrap" dir="ltr"><?= e($h['created_at']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

