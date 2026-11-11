<?php
/** مكوّنات واجهة قابلة لإعادة الاستخدام. */

/** يرسم حقل إدخال من تعريفه في tools.php. */
function render_field(array $f, string $prefix): string
{
    $id = e($prefix . '-' . $f['name']);
    $name = e($f['name']);
    $label = e($f['label']);
    $value = $f['value'] ?? '';
    $required = ($f['required'] ?? true) ? ' required' : '';
    $attrs = '';
    foreach (['min', 'max', 'step', 'placeholder', 'maxlength'] as $a) {
        if (isset($f[$a])) {
            $attrs .= ' ' . $a . '="' . e($f[$a]) . '"';
        }
    }

    switch ($f['type']) {
        case 'checkbox':
            $checked = $value ? ' checked' : '';
            return "<div class=\"form-check mb-3\"><input class=\"form-check-input\" type=\"checkbox\" id=\"$id\" name=\"$name\" value=\"1\"$checked>"
                . "<label class=\"form-check-label\" for=\"$id\">$label</label></div>";

        case 'select':
            $opts = '';
            foreach ($f['options'] as $k => $text) {
                $sel = (string) $k === (string) $value ? ' selected' : '';
                $opts .= '<option value="' . e($k) . "\"$sel>" . e($text) . '</option>';
            }
            return "<div class=\"mb-3\"><label class=\"form-label\" for=\"$id\">$label</label>"
                . "<select class=\"form-select\" id=\"$id\" name=\"$name\"$required>$opts</select></div>";

        case 'range':
            $suffix = e($f['suffix'] ?? '');
            return "<div class=\"mb-3\"><label class=\"form-label d-flex justify-content-between\" for=\"$id\">$label <output class=\"fw-bold\" data-for=\"$id\">" . e($value) . "$suffix</output></label>"
                . "<input type=\"range\" class=\"form-range\" id=\"$id\" name=\"$name\" value=\"" . e($value) . "\" data-suffix=\"$suffix\"$attrs></div>";

        default: // number | text
            $type = $f['type'] === 'number' ? 'number' : 'text';
            $input = "<input type=\"$type\" class=\"form-control\" id=\"$id\" name=\"$name\" value=\"" . e($value) . "\"$attrs$required>";
            if (!empty($f['media_time'])) {
                $input = "<div class=\"input-group\">$input<button class=\"btn btn-outline-secondary\" type=\"button\" data-media-time=\"$id\" title=\"استخدم موضع التشغيل الحالي\"><i class=\"bi bi-stopwatch\"></i> الآن</button></div>";
            }
            return "<div class=\"mb-3\"><label class=\"form-label\" for=\"$id\">$label</label>$input</div>";
    }
}

/** صندوق مشاركة: رابط + نسخ + QR + تنزيل QR. */
function share_box(string $url): string
{
    $u = e($url);
    return <<<HTML
    <div class="share-box">
        <div class="qr" data-qr="$u"></div>
        <div class="flex-grow-1 w-100">
            <label class="form-label small text-secondary">رابط المشاركة</label>
            <div class="input-group mb-2" dir="ltr">
                <input type="text" class="form-control" value="$u" readonly>
                <button class="btn btn-accent" type="button" data-copy="$u"><i class="bi bi-clipboard"></i> نسخ</button>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-light btn-sm" href="$u" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> فتح</a>
                <button class="btn btn-light btn-sm" type="button" data-qr-download="$u"><i class="bi bi-download"></i> تنزيل QR</button>
            </div>
        </div>
    </div>
    HTML;
}

