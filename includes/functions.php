<?php
/** دوال مساعدة مشتركة: الهروب، CSRF، الرسائل، المستخدم، الرفع، والتحقق. */

/** هروب المخرجات لمنع XSS — استخدمها لكل قيمة تُطبع في HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** رابط مطلق داخل التطبيق (يُستخدم لروابط المشاركة و QR). */
function app_url(string $path = ''): string
{
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $dir    = preg_replace('#/api$#', '', $dir); // ملفات api/ تشارك نفس الجذر
    return $scheme . '://' . $host . $dir . '/' . ltrim($path, '/');
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** يتحقق من رمز CSRF ومن أن الطلب لم يتجاوز post_max_size. */
function verify_csrf(bool $json = false): void
{
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        fail_request('حجم الطلب أكبر من الحد الذي يسمح به الخادم (' . format_bytes(upload_limit_bytes()) . ').', 413, $json);
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        fail_request('انتهت صلاحية النموذج، حدّث الصفحة وحاول مرة أخرى.', 419, $json);
    }
}

function fail_request(string $message, int $code, bool $json): never
{
    if ($json) {
        json_response(['error' => $message], $code);
    }
    flash('danger', $message);
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- رسائل الفلاش ---------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------------- المستخدم والجلسة ---------------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function login_user(int $id): void
{
    session_regenerate_id(true); // منع تثبيت الجلسة (session fixation)
    $_SESSION['user_id'] = $id;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'سجّل دخولك أولاً للوصول إلى هذه الصفحة.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    return $user;
}

/** يقبل فقط مسارات داخلية لمنع إعادة التوجيه المفتوح (open redirect). */
function safe_next(?string $next): string
{
    $next = (string) $next;
    if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, '\\')) {
        return 'dashboard.php';
    }
    return $next;
}

/* ---------------- الملفات والرفع ---------------- */

function format_bytes(int|float $bytes): string
{
    $units = ['بايت', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, $i ? 1 : 0) . ' ' . $units[$i];
}

function ini_bytes(string $value): int
{
    $value = trim($value);
    $num = (int) $value;
    return match (strtolower(substr($value, -1))) {
        'g' => $num * 1024 ** 3,
        'm' => $num * 1024 ** 2,
        'k' => $num * 1024,
        default => $num,
    };
}

/** الحد الفعلي للرفع = الأصغر بين إعداد التطبيق وإعدادات PHP. */
function upload_limit_bytes(): int
{
    return min(MAX_UPLOAD_MB * 1024 * 1024, ini_bytes(ini_get('upload_max_filesize')), ini_bytes(ini_get('post_max_size')));
}

/** يحوّل $_FILES['x'] (مفرد أو متعدد) إلى قائمة موحّدة. */
function uploaded_files(string $field): array
{
    if (empty($_FILES[$field])) {
        return [];
    }
    $f = $_FILES[$field];
    if (!is_array($f['name'])) {
        return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    }
    $list = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $list;
}

/** يرجع رسالة خطأ للملف المرفوع أو null إن كان سليماً. */
function upload_error(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > upload_limit_bytes()) {
        return 'الملف «' . $file['name'] . '» أكبر من الحد المسموح (' . format_bytes(upload_limit_bytes()) . ').';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return 'فشل رفع الملف «' . $file['name'] . '»، حاول مرة أخرى.';
    }
    if ($file['size'] === 0) {
        return 'الملف «' . $file['name'] . '» فارغ.';
    }
    return null;
}

function file_ext(string $name): string
{
    return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

/** اسم ملف آمن للعرض والتنزيل (بدون مسارات أو رموز تحكم). */
function clean_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F"<>:|?*]/u', '', $name) ?? 'file';
    return mb_substr($name, 0, 200) ?: 'file';
}

function detect_mime(string $path): string
{
    return (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
}

function category_of_mime(string $mime): string
{
    return match (true) {
        str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml' => 'image',
        str_starts_with($mime, 'video/') => 'video',
        str_starts_with($mime, 'audio/') => 'audio',
        $mime === 'application/pdf', str_starts_with($mime, 'text/'),
        str_contains($mime, 'word'), str_contains($mime, 'excel'), str_contains($mime, 'spreadsheet'),
        str_contains($mime, 'presentation'), str_contains($mime, 'powerpoint'), $mime === 'application/json' => 'document',
        default => 'other',
    };
}

/* ---------------- التحقق العام من حقول الأدوات ---------------- */

/** تعريف الأقسام والأدوات (يُحمّل مرة واحدة). */
function tool_categories(): array
{
    static $cats = null;
    return $cats ??= require __DIR__ . '/tools.php';
}

/**
 * يتحقق من المدخلات بالاعتماد على تعريف الحقول في tools.php
 * (نفس التعريف يُستخدم لرسم النموذج — مصدر واحد للحقيقة).
 * @return array{0: array, 1: string[]} [القيم النظيفة, الأخطاء]
 */
function validate_fields(array $fields, array $input): array
{
    $clean = [];
    $errors = [];
    foreach ($fields as $f) {
        $name = $f['name'];
        $raw = $input[$name] ?? null;
        $required = $f['required'] ?? true;
        $label = $f['label'];

        if ($f['type'] === 'checkbox') {
            $clean[$name] = !empty($raw);
            continue;
        }
        $raw = is_string($raw) ? trim($raw) : '';
        if ($raw === '') {
            if ($required) {
                $errors[] = "الحقل «{$label}» مطلوب.";
            }
            $clean[$name] = null;
            continue;
        }
        switch ($f['type']) {
            case 'number':
            case 'range':
                $isFloat = isset($f['step']) && (float) $f['step'] != (int) $f['step'];
                $val = filter_var($raw, $isFloat ? FILTER_VALIDATE_FLOAT : FILTER_VALIDATE_INT);
                if ($val === false || (isset($f['min']) && $val < $f['min']) || (isset($f['max']) && $val > $f['max'])) {
                    $errors[] = "قيمة «{$label}» يجب أن تكون رقماً بين " . ($f['min'] ?? '-∞') . ' و ' . ($f['max'] ?? '∞') . '.';
                    break;
                }
                $clean[$name] = $val;
                break;
            case 'select':
                if (!array_key_exists($raw, $f['options'])) {
                    $errors[] = "اختيار غير صالح في «{$label}».";
                    break;
                }
                $clean[$name] = $raw;
                break;
            default: // text
                if (mb_strlen($raw) > ($f['maxlength'] ?? 255)) {
                    $errors[] = "«{$label}» أطول من المسموح.";
                    break;
                }
                $clean[$name] = $raw;
        }
    }
    return [$clean, $errors];
}

/* ---------------- قاعدة البيانات: عمليات مشتركة ---------------- */

/** يسجّل استخدام أداة (لعرض الإحصائيات وسجل المستخدم). */
function log_operation(string $category, string $tool, ?string $fileName): void
{
    $stmt = db()->prepare('INSERT INTO operations (user_id, category, tool, file_name) VALUES (?, ?, ?, ?)');
    $stmt->execute([current_user()['id'] ?? null, $category, $tool, $fileName ? mb_substr($fileName, 0, 255) : null]);
}

/** تنظيف كسول: يحذف الملفات المنتهية (من القرص ثم من القاعدة). */
function cleanup_expired_shares(): void
{
    $rows = db()->query('SELECT id, stored_name FROM shares WHERE expires_at IS NOT NULL AND expires_at < NOW() LIMIT 50')->fetchAll();
    if (!$rows) {
        return;
    }
    $del = db()->prepare('DELETE FROM shares WHERE id = ?');
    foreach ($rows as $r) {
        @unlink(STORAGE_DIR . '/' . $r['stored_name']);
        $del->execute([$r['id']]);
    }
}

/** يجلب الملف المشارك برمزه (مع حقل expired محسوب من قاعدة البيانات). */
function find_share(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{16}$/', $token)) {
        return null;
    }
    $stmt = db()->prepare('SELECT s.*, (s.expires_at IS NOT NULL AND s.expires_at < NOW()) AS expired FROM shares s WHERE s.token = ?');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/**
 * يرفع الملف إلى خدمة استضافة خارجية مجانية (x0.at ثم uguu.se ثم tmpfiles.org)
 * للحصول على رابط عام (Public URL) يعمل من أي جهاز أو جوال خارج localhost.
 */
function upload_to_external_host(string $filePath, string $fileName, string $mime): ?string
{
    $safeName = clean_filename($fileName);

    // 1) x0.at — رابط مباشر قصير جداً
    $res = multipart_upload('https://x0.at', 'file', $filePath, $safeName, $mime, 'curl/8.5.0');
    if ($res && preg_match('#^https://x0\.at/\S+$#', trim($res))) {
        return trim($res);
    }

    // 2) uguu.se — رابط مباشر سريع حتى 128MB
    $res = multipart_upload('https://uguu.se/upload', 'files[]', $filePath, $safeName, $mime);
    if ($res) {
        $json = json_decode($res, true);
        if (!empty($json['files'][0]['url'])) {
            return (string) $json['files'][0]['url'];
        }
    }

    // 3) tmpfiles.org — بديل إضافي مع تحويل الرابط إلى رابط تنزيل مباشر
    $res = multipart_upload('https://tmpfiles.org/api/v1/upload', 'file', $filePath, $safeName, $mime);
    if ($res) {
        $json = json_decode($res, true);
        if (!empty($json['data']['url'])) {
            return (string) preg_replace('#^https?://tmpfiles\.org/#', 'https://tmpfiles.org/dl/', $json['data']['url']);
        }
    }

    return null;
}

/** يرسل ملفاً عبر POST multipart/form-data باستخدام cURL أو Streams كبديل. */
function multipart_upload(string $url, string $field, string $filePath, string $fileName, string $mime, string $ua = 'Mozilla/5.0 JuFile/1.0'): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [$field => new CURLFile($filePath, $mime, $fileName)],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_USERAGENT      => $ua,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
    }

    // بديل بدون cURL عبر PHP Streams
    $boundary = '----JuFileBoundary' . bin2hex(random_bytes(8));
    $content  = "--$boundary\r\n"
        . "Content-Disposition: form-data; name=\"$field\"; filename=\"" . addslashes($fileName) . "\"\r\n"
        . "Content-Type: $mime\r\n\r\n"
        . file_get_contents($filePath) . "\r\n"
        . "--$boundary--\r\n";
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: multipart/form-data; boundary=$boundary\r\nUser-Agent: $ua\r\n",
            'content' => $content,
            'timeout' => 25,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    return $res !== false ? $res : null;
}

/** يرجع الرابط الخارجي العام إن وُجد، وإلا رابط الصفحة المحلية. */
function share_public_url(array $share): string
{
    return !empty($share['external_url']) ? $share['external_url'] : app_url('f.php?t=' . $share['token']);
}

/** هل يحق للزائر الحالي الوصول لملف محمي بكلمة مرور؟ */
function share_unlocked(array $share): bool
{
    return $share['password_hash'] === null
        || !empty($_SESSION['unlocked'][$share['token']])
        || (current_user() && (int) current_user()['id'] === (int) $share['user_id']);
}

/** خيارات مدة صلاحية الرابط (الزائر له خيارات محدودة). */
function expiry_options(bool $loggedIn): array
{
    $opts = ['1' => 'ساعة واحدة', '24' => 'يوم واحد', '168' => '7 أيام'];
    if ($loggedIn) {
        $opts += ['720' => '30 يوماً', 'never' => 'بدون انتهاء'];
    }
    return $opts;
}

function category_label(string $cat): string
{
    return ['image' => 'صورة', 'video' => 'فيديو', 'audio' => 'صوت', 'document' => 'مستند', 'other' => 'ملف'][$cat] ?? 'ملف';
}

function category_icon(string $cat): string
{
    return ['image' => 'bi-image', 'video' => 'bi-camera-video', 'audio' => 'bi-music-note-beamed', 'document' => 'bi-file-earmark-text', 'other' => 'bi-file-earmark'][$cat] ?? 'bi-file-earmark';
}
