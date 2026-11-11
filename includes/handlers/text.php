<?php
/** أدوات الملفات النصية (CSV/JSON/TXT...) على الخادم. */

function text_tool(string $tool, array $file, array $o): array
{
    $raw = file_get_contents($file['tmp_name']);
    // الملفات النصية لا تحتوي بايت NULL (إلا UTF-16 الذي تعالجه أداة الترميز)
    if ($tool !== 'text_encoding' && str_contains(substr($raw, 0, 8000), "\0")) {
        throw new ToolError('هذا الملف ليس ملفاً نصياً. إن كان بترميز UTF-16 استخدم أداة «إصلاح الترميز» أولاً.');
    }
    $base = pathinfo(clean_filename($file['name']), PATHINFO_FILENAME);
    $text = $tool === 'text_encoding' ? $raw : strip_bom($raw);

    switch ($tool) {
        case 'text_convert':
            return text_convert($o['mode'], $text, $base);

        case 'text_clean':
            $lines = preg_split('/\r\n|\r|\n/', $text);
            $out = match ($o['action']) {
                'spaces'      => implode("\n", array_map(fn ($l) => trim(preg_replace('/[ \t]+/u', ' ', $l)), $lines)),
                'empty_lines' => implode("\n", array_filter($lines, fn ($l) => trim($l) !== '')),
                'unique'      => implode("\n", array_unique($lines)),
                'sort'        => (function () use ($lines) {
                    sort($lines, SORT_NATURAL | SORT_FLAG_CASE);
                    return implode("\n", $lines);
                })(),
                'upper'       => mb_strtoupper($text),
                'lower'       => mb_strtolower($text),
            };
            $removed = count($lines) - count(preg_split('/\n/', $out));
            return ['data' => $out, 'name' => "$base-clean.txt", 'mime' => 'text/plain; charset=utf-8',
                    'info' => $removed > 0 ? "حُذف $removed سطر" : null];

        case 'text_replace':
            $count = 0;
            $out = $o['case_sensitive']
                ? str_replace($o['find'], $o['replace'] ?? '', $text, $count)
                : preg_replace('/' . preg_quote($o['find'], '/') . '/iu', $o['replace'] ?? '', $text, -1, $count);
            if ($out === null) {
                throw new ToolError('تعذّر تنفيذ الاستبدال، تأكد أن الملف بترميز UTF-8.');
            }
            return ['data' => $out, 'name' => $base . '-replaced.' . file_ext($file['name']), 'mime' => 'text/plain; charset=utf-8',
                    'info' => "تم استبدال $count موضع"];

        case 'text_encoding':
            return ['data' => to_utf8($raw, $o['from']), 'name' => $base . '-utf8.' . file_ext($file['name']), 'mime' => 'text/plain; charset=utf-8',
                    'info' => 'تم التحويل إلى UTF-8'];

        case 'text_stats':
            return ['json' => ['stats' => text_stats($text)]];
    }
    throw new ToolError('أداة غير معروفة.');
}

function strip_bom(string $s): string
{
    return str_starts_with($s, "\xEF\xBB\xBF") ? substr($s, 3) : $s;
}

function to_utf8(string $raw, string $from): string
{
    if ($from === 'auto') {
        $from = match (true) {
            str_starts_with($raw, "\xFF\xFE"), str_starts_with($raw, "\xFE\xFF") => 'UTF-16',
            mb_check_encoding($raw, 'UTF-8') => 'UTF-8',
            default => 'CP1256', // الأكثر شيوعاً للملفات العربية القديمة
        };
    }
    if ($from === 'UTF-8') {
        return strip_bom($raw);
    }
    $out = @iconv($from, 'UTF-8//IGNORE', $raw);
    if ($out === false || $out === '') {
        throw new ToolError('تعذّر التحويل من الترميز المحدد.');
    }
    return strip_bom($out);
}

function text_convert(string $mode, string $text, string $base): array
{
    switch ($mode) {
        case 'csv_json':
            $first = strtok($text, "\n") ?: '';
            $delims = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
            $delim = array_search(max($delims), $delims, true);
            $fh = fopen('php://temp', 'r+');
            fwrite($fh, $text);
            rewind($fh);
            $rows = [];
            while (($r = fgetcsv($fh, null, $delim, '"', '\\')) !== false) {
                if ($r !== [null]) { // تجاهل الأسطر الفارغة
                    $rows[] = $r;
                }
            }
            if (count($rows) < 2) {
                throw new ToolError('ملف CSV يجب أن يحتوي على سطر عناوين وسطر بيانات على الأقل.');
            }
            $headers = array_map('trim', array_shift($rows));
            $data = array_map(fn ($r) => array_combine($headers, array_pad(array_slice($r, 0, count($headers)), count($headers), '')), $rows);
            return ['data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'name' => "$base.json", 'mime' => 'application/json', 'info' => count($data) . ' صف'];

        case 'json_csv':
            $data = json_decode($text, true);
            if (!is_array($data) || !array_is_list($data) || !$data) {
                throw new ToolError('يجب أن يكون JSON مصفوفة كائنات، مثل: [{"name":"..."}].');
            }
            $headers = [];
            foreach ($data as $row) {
                $headers += array_flip(array_keys(is_array($row) ? $row : ['value' => $row]));
            }
            $headers = array_keys($headers);
            $fh = fopen('php://temp', 'r+');
            fwrite($fh, "\xEF\xBB\xBF"); // BOM ليظهر العربي صحيحاً في Excel
            fputcsv($fh, $headers, ',', '"', '\\');
            foreach ($data as $row) {
                $row = is_array($row) ? $row : ['value' => $row];
                fputcsv($fh, array_map(fn ($h) => is_scalar($row[$h] ?? '') ? ($row[$h] ?? '') : json_encode($row[$h], JSON_UNESCAPED_UNICODE), $headers), ',', '"', '\\');
            }
            rewind($fh);
            return ['data' => stream_get_contents($fh), 'name' => "$base.csv", 'mime' => 'text/csv; charset=utf-8', 'info' => count($data) . ' صف'];

        case 'json_pretty':
        case 'json_minify':
            $data = json_decode($text);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ToolError('JSON غير صالح: ' . json_last_error_msg());
            }
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($mode === 'json_pretty' ? JSON_PRETTY_PRINT : 0);
            return ['data' => json_encode($data, $flags), 'name' => "$base.json", 'mime' => 'application/json'];

        case 'txt_html':
            $paragraphs = preg_split('/(\r?\n){2,}/', trim($text));
            $body = implode("\n", array_map(fn ($p) => '<p>' . nl2br(e($p)) . '</p>', $paragraphs));
            $html = "<!doctype html>\n<html lang=\"ar\" dir=\"auto\">\n<head><meta charset=\"utf-8\"><title>" . e($base) . "</title>"
                . "<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;line-height:1.8;padding:0 16px}</style></head>\n<body>\n$body\n</body>\n</html>";
            return ['data' => $html, 'name' => "$base.html", 'mime' => 'text/html; charset=utf-8'];
    }
    throw new ToolError('تحويل غير معروف.');
}

function text_stats(string $text): array
{
    if (!mb_check_encoding($text, 'UTF-8')) {
        throw new ToolError('الملف ليس بترميز UTF-8. استخدم أداة «إصلاح الترميز» أولاً.');
    }
    preg_match_all('/[\p{L}\p{N}\']+/u', $text, $m);
    $words = $m[0];
    $freq = array_count_values(array_map('mb_strtolower', array_filter($words, fn ($w) => mb_strlen($w) > 2)));
    arsort($freq);
    return [
        'الكلمات'            => count($words),
        'الأحرف'             => mb_strlen($text),
        'الأحرف بدون مسافات' => mb_strlen(preg_replace('/\s+/u', '', $text)),
        'الأسطر'             => $text === '' ? 0 : count(preg_split('/\r\n|\r|\n/', $text)),
        'الفقرات'            => count(array_filter(preg_split('/(\r?\n){2,}/', $text), fn ($p) => trim($p) !== '')),
        'وقت القراءة'        => max(1, (int) ceil(count($words) / 200)) . ' دقيقة',
        'الأكثر تكراراً'     => implode('، ', array_map(fn ($w, $c) => "$w ($c)", array_keys(array_slice($freq, 0, 8, true)), array_slice($freq, 0, 8, true))),
    ];
}
