<?php

namespace App\Services;

class ToolCatalog
{
    public const MAX_UPLOAD_BYTES = 50 * 1024 * 1024; // 50 MB
    public const MAX_IMAGE_PIXELS = 45_000_000;

    public static function categories(): array
    {
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
        $videoExt = ['mp4', 'webm', 'mov', 'mkv', 'avi', 'm4v', '3gp'];
        $audioExt = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma'];
        $textExt  = ['txt', 'csv', 'json', 'md', 'html', 'xml', 'log', 'srt'];

        $start = ['name' => 'start', 'label' => 'البداية (ثانية)', 'type' => 'number', 'min' => 0, 'step' => 0.1, 'value' => 0, 'media_time' => true];
        $end   = ['name' => 'end', 'label' => 'النهاية (ثانية)', 'type' => 'number', 'min' => 0.1, 'step' => 0.1, 'value' => null, 'media_time' => true];

        return [
            'image' => [
                'title' => 'الصور', 'icon' => 'bi-image', 'route' => 'tools.category',
                'desc'  => 'صغّر حجم صورك، غيّر أبعادها، قصّها، دوّرها، أو حوّل صيغتها.',
                'tools' => [
                    'compress' => [
                        'title' => 'تصغير حجم الصورة', 'icon' => 'bi-arrows-collapse', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => 'قلّل حجم الصورة مع الحفاظ على جودة ممتازة.',
                        'fields' => [
                            ['name' => 'quality', 'label' => 'الجودة', 'type' => 'range', 'min' => 10, 'max' => 95, 'value' => 70, 'suffix' => '%'],
                            ['name' => 'format', 'label' => 'صيغة الناتج', 'type' => 'select', 'value' => 'keep', 'options' => ['keep' => 'نفس الصيغة', 'jpg' => 'JPG', 'webp' => 'WebP (الأصغر)']],
                            ['name' => 'max_width', 'label' => 'أقصى عرض بالبكسل (اختياري)', 'type' => 'number', 'min' => 16, 'max' => 10000, 'required' => false, 'placeholder' => 'بدون تغيير'],
                        ],
                    ],
                    'resize' => [
                        'title' => 'تغيير مقاس الصورة', 'icon' => 'bi-aspect-ratio', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => 'حدّد العرض أو الارتفاع الجديد بالبكسل.',
                        'fields' => [
                            ['name' => 'width', 'label' => 'العرض (px)', 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                            ['name' => 'height', 'label' => 'الارتفاع (px)', 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                            ['name' => 'keep_ratio', 'label' => 'حافظ على تناسب الأبعاد', 'type' => 'checkbox', 'value' => true],
                        ],
                    ],
                    'crop' => [
                        'title' => 'قص الصورة', 'icon' => 'bi-crop', 'engine' => 'server', 'accept' => $imageExt, 'crop_ui' => true,
                        'desc' => 'اسحب على الصورة لتحديد الجزء المطلوب قصّه.',
                        'fields' => [
                            ['name' => 'x', 'label' => 'من اليسار X', 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'y', 'label' => 'من الأعلى Y', 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'width', 'label' => 'العرض', 'type' => 'number', 'min' => 1, 'max' => 20000],
                            ['name' => 'height', 'label' => 'الارتفاع', 'type' => 'number', 'min' => 1, 'max' => 20000],
                        ],
                    ],
                    'rotate' => [
                        'title' => 'تدوير وقلب الصورة', 'icon' => 'bi-arrow-clockwise', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => 'دوّر الصورة 90° أو 180° أو اقلبها أفقياً وعمودياً.',
                        'fields' => [
                            ['name' => 'angle', 'label' => 'التدوير', 'type' => 'select', 'value' => '90', 'options' => ['0' => 'بدون', '90' => '90° مع عقارب الساعة', '180' => '180°', '270' => '90° عكس عقارب الساعة']],
                            ['name' => 'flip', 'label' => 'القلب', 'type' => 'select', 'value' => 'none', 'options' => ['none' => 'بدون', 'h' => 'أفقي (مرآة)', 'v' => 'عمودي']],
                        ],
                    ],
                    'convert' => [
                        'title' => 'تحويل صيغة الصورة', 'icon' => 'bi-arrow-left-right', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => 'حوّل بين JPG و PNG و WebP و GIF و BMP.',
                        'fields' => [
                            ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'png', 'options' => ['jpg' => 'JPG', 'png' => 'PNG', 'webp' => 'WebP', 'gif' => 'GIF', 'bmp' => 'BMP']],
                        ],
                    ],
                    'filter' => [
                        'title' => 'فلاتر وتأثيرات لونية', 'icon' => 'bi-magic', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => 'أبيض وأسود، سيبيا، تمويه، وزيادة الحدة أو الإضاءة.',
                        'fields' => [
                            ['name' => 'effect', 'label' => 'التأثير', 'type' => 'select', 'value' => 'grayscale', 'options' => ['grayscale' => 'أبيض وأسود', 'sepia' => 'سيبيا (قديم)', 'negate' => 'عكس الألوان', 'blur' => 'تمويه', 'sharpen' => 'زيادة الحدة', 'brighten' => 'زيادة الإضاءة']],
                        ],
                    ],
                ],
            ],

            'video' => [
                'title' => 'الفيديو', 'icon' => 'bi-camera-video', 'route' => 'tools.category',
                'desc'  => 'صغّر حجم الفيديو، قصّ جزءاً منه، اضبط أبعاده، أو حوّل صيغته — داخل متصفحك.',
                'tools' => [
                    'compress' => [
                        'title' => 'ضغط وتصغير الفيديو', 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => 'ضغط الفيديو إلى MP4 بحجم أصغر.',
                        'fields' => [
                            ['name' => 'quality', 'label' => 'مستوى الضغط', 'type' => 'select', 'value' => '28', 'options' => ['23' => 'خفيف (جودة عالية)', '28' => 'متوسط (موصى به)', '33' => 'قوي (أصغر حجم)']],
                            ['name' => 'max_height', 'label' => 'أقصى دقة', 'type' => 'select', 'value' => '720', 'options' => ['0' => 'بدون تغيير', '1080' => '1080p', '720' => '720p', '480' => '480p', '360' => '360p']],
                        ],
                    ],
                    'trim' => [
                        'title' => 'قص جزء من الفيديو', 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $videoExt, 'trim_ui' => true,
                        'desc' => 'اسحب مقبضي البداية والنهاية لتحديد المقطع المطلوب.',
                        'fields' => [$start, $end, ['name' => 'fast', 'label' => 'قص سريع بدون إعادة ترميز (أقل دقة)', 'type' => 'checkbox', 'value' => true]],
                    ],
                    'crop' => [
                        'title' => 'قص أبعاد الفيديو', 'icon' => 'bi-crop', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => 'غيّر نسبة الإطار لتناسب الستوري أو الريلز أو يوتيوب.',
                        'fields' => [
                            ['name' => 'ratio', 'label' => 'النسبة', 'type' => 'select', 'value' => '9:16', 'options' => ['9:16' => '9:16 (ستوري / ريلز)', '1:1' => '1:1 (مربع)', '4:5' => '4:5 (منشور)', '16:9' => '16:9 (عريض)', '4:3' => '4:3']],
                        ],
                    ],
                    'convert' => [
                        'title' => 'تحويل صيغة الفيديو', 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => 'MP4، WebM، MOV، MKV، AVI أو GIF متحرك.',
                        'fields' => [
                            ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'mp4', 'options' => ['mp4' => 'MP4', 'webm' => 'WebM', 'mov' => 'MOV', 'mkv' => 'MKV', 'avi' => 'AVI', 'gif' => 'GIF متحرك (أول 15 ثانية)']],
                        ],
                    ],
                    'extract_audio' => [
                        'title' => 'استخراج الصوت فقط', 'icon' => 'bi-music-note', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => 'احفظ صوت الفيديو كملف صوتي مستقل.',
                        'fields' => [
                            ['name' => 'format', 'label' => 'صيغة الصوت', 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'm4a' => 'M4A']],
                        ],
                    ],
                    'mute' => [
                        'title' => 'إزالة الصوت من الفيديو', 'icon' => 'bi-volume-mute', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => 'احذف المسار الصوتي واجعل الفيديو صامتاً.', 'fields' => [],
                    ],
                ],
            ],

            'audio' => [
                'title' => 'الصوت', 'icon' => 'bi-music-note-beamed', 'route' => 'tools.category',
                'desc'  => 'حوّل صيغة ملفاتك الصوتية، صغّر حجمها، قصّها أو عدّل مستوى الصوت.',
                'tools' => [
                    'convert' => [
                        'title' => 'تحويل صيغة الصوت', 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => 'MP3، WAV، OGG، M4A أو FLAC.',
                        'fields' => [
                            ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'ogg' => 'OGG', 'm4a' => 'M4A', 'flac' => 'FLAC']],
                        ],
                    ],
                    'compress' => [
                        'title' => 'ضغط وتصغير الصوت', 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => 'خفّض معدل البت للحصول على ملف MP3 أصغر.',
                        'fields' => [
                            ['name' => 'bitrate', 'label' => 'معدل البت', 'type' => 'select', 'value' => '96', 'options' => ['128' => '128k (جيد)', '96' => '96k (متوسط)', '64' => '64k (صغير)', '32' => '32k (صوت كلام)']],
                            ['name' => 'mono', 'label' => 'تحويل إلى أحادي (Mono)', 'type' => 'checkbox', 'value' => false],
                        ],
                    ],
                    'trim' => [
                        'title' => 'قص مقطع صوتي', 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $audioExt, 'trim_ui' => true,
                        'desc' => 'اسحب مقبضي البداية والنهاية لتحديد المقطع الصوتي.', 'fields' => [$start, $end],
                    ],
                    'volume' => [
                        'title' => 'رفع أو خفض الصوت', 'icon' => 'bi-volume-up', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => 'ارفع أو اخفض مستوى الصوت، أو وازنه تلقائياً.',
                        'fields' => [
                            ['name' => 'level', 'label' => 'المستوى', 'type' => 'select', 'value' => '1.5', 'options' => ['0.5' => '50%', '0.75' => '75%', '1.25' => '125%', '1.5' => '150%', '2' => '200%', 'normalize' => 'موازنة تلقائية']],
                        ],
                    ],
                ],
            ],

            'document' => [
                'title' => 'المستندات', 'icon' => 'bi-file-earmark-text', 'route' => 'tools.category',
                'desc'  => 'أدوات PDF، تحويل الملفات النصية، تنظيفها، وضغط الملفات في ZIP.',
                'tools' => [
                    'pdf_merge' => [
                        'title' => 'دمج ملفات PDF', 'icon' => 'bi-union', 'engine' => 'pdf', 'accept' => ['pdf'], 'multi' => true,
                        'desc' => 'اجمع عدة ملفات PDF في ملف واحد بالترتيب.', 'fields' => [],
                    ],
                    'pdf_extract' => [
                        'title' => 'استخراج صفحات PDF', 'icon' => 'bi-file-earmark-break', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => 'أنشئ ملف PDF جديداً من صفحات محددة.',
                        'fields' => [['name' => 'pages', 'label' => 'الصفحات (مثال: 1-3,5)', 'type' => 'text', 'placeholder' => '1-3,5']],
                    ],
                    'pdf_delete' => [
                        'title' => 'حذف صفحات من PDF', 'icon' => 'bi-file-earmark-x', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => 'احذف صفحات لا تحتاجها من ملف PDF.',
                        'fields' => [['name' => 'pages', 'label' => 'الصفحات المحذوفة (مثال: 2,4-6)', 'type' => 'text', 'placeholder' => '2,4-6']],
                    ],
                    'pdf_rotate' => [
                        'title' => 'تدوير صفحات PDF', 'icon' => 'bi-arrow-clockwise', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => 'دوّر كل الصفحات أو صفحات محددة.',
                        'fields' => [
                            ['name' => 'angle', 'label' => 'الزاوية', 'type' => 'select', 'value' => '90', 'options' => ['90' => '90°', '180' => '180°', '270' => '270°']],
                            ['name' => 'pages', 'label' => 'الصفحات (فارغ = الكل)', 'type' => 'text', 'required' => false, 'placeholder' => 'الكل'],
                        ],
                    ],
                    'images_pdf' => [
                        'title' => 'تحويل الصور إلى PDF', 'icon' => 'bi-images', 'engine' => 'pdf', 'accept' => $imageExt, 'multi' => true,
                        'desc' => 'اجمع صورة أو أكثر في ملف PDF واحد.',
                        'fields' => [['name' => 'size', 'label' => 'حجم الصفحة', 'type' => 'select', 'value' => 'a4', 'options' => ['a4' => 'A4', 'fit' => 'بحجم الصورة']]],
                    ],
                    'text_convert' => [
                        'title' => 'تحويل صيغ النصوص (CSV/JSON)', 'icon' => 'bi-filetype-json', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => 'CSV ⇄ JSON، نص إلى HTML، وتنسيق JSON.',
                        'fields' => [['name' => 'mode', 'label' => 'التحويل', 'type' => 'select', 'value' => 'csv_json', 'options' => [
                            'csv_json' => 'CSV ← إلى JSON', 'json_csv' => 'JSON ← إلى CSV', 'txt_html' => 'نص ← إلى HTML', 'json_pretty' => 'تنسيق JSON (مقروء)', 'json_minify' => 'تصغير JSON',
                        ]]],
                    ],
                    'text_clean' => [
                        'title' => 'تنظيف وترتيب النصوص', 'icon' => 'bi-stars', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => 'حذف الفراغات الزائدة، الأسطر الفارغة، الترتيب والتكرار.',
                        'fields' => [['name' => 'action', 'label' => 'العملية', 'type' => 'select', 'value' => 'spaces', 'options' => [
                            'spaces' => 'حذف الفراغات الزائدة', 'empty_lines' => 'حذف الأسطر الفارغة', 'unique' => 'حذف الأسطر المكررة', 'sort' => 'ترتيب الأسطر أبجدياً', 'upper' => 'أحرف إنجليزية كبيرة', 'lower' => 'أحرف إنجليزية صغيرة',
                        ]]],
                    ],
                    'text_replace' => [
                        'title' => 'بحث واستبدال في النص', 'icon' => 'bi-search', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => 'استبدل كلمة أو عبارة في كامل الملف.',
                        'fields' => [
                            ['name' => 'find', 'label' => 'ابحث عن', 'type' => 'text', 'maxlength' => 500],
                            ['name' => 'replace', 'label' => 'استبدل بـ', 'type' => 'text', 'required' => false, 'maxlength' => 500],
                            ['name' => 'case_sensitive', 'label' => 'مطابقة حالة الأحرف', 'type' => 'checkbox', 'value' => false],
                        ],
                    ],
                    'text_encoding' => [
                        'title' => 'إصلاح حروف العربي المشوّهة', 'icon' => 'bi-translate', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => 'حوّل الملفات ذات الترميز القديم إلى UTF-8 مقروء.',
                        'fields' => [['name' => 'from', 'label' => 'الترميز الأصلي', 'type' => 'select', 'value' => 'auto', 'options' => [
                            'auto' => 'تلقائي', 'CP1256' => 'Windows-1256 (عربي)', 'ISO-8859-6' => 'ISO-8859-6 (عربي)', 'UTF-16' => 'UTF-16', 'CP1252' => 'Windows-1252 (لاتيني)',
                        ]]],
                    ],
                    'text_stats' => [
                        'title' => 'عدّ الكلمات والأحرف', 'icon' => 'bi-bar-chart', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => 'إحصائيات الكلمات والأحرف والأسطر ووقت القراءة.', 'fields' => [],
                    ],
                    'zip' => [
                        'title' => 'ضغط الملفات في ZIP', 'icon' => 'bi-file-zip', 'engine' => 'server', 'accept' => [], 'multi' => true,
                        'desc' => 'اجمع عدة ملفات في أرشيف ZIP واحد.',
                        'fields' => [['name' => 'archive', 'label' => 'اسم الأرشيف', 'type' => 'text', 'required' => false, 'maxlength' => 80, 'placeholder' => 'files']],
                    ],
                ],
            ],
        ];
    }

    public static function expiryOptions(bool $loggedIn): array
    {
        $opts = ['1' => 'ساعة واحدة', '24' => '24 ساعة', '168' => '7 أيام'];
        if ($loggedIn) {
            $opts += ['720' => '30 يوماً', 'never' => 'بدون انتهاء'];
        }
        return $opts;
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['بايت', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return round($n, $i ? 1 : 0) . ' ' . $units[$i];
    }

    public static function cleanFilename(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', basename($name));
        return trim($name, '._') ?: 'file';
    }

    public static function categoryOfMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            $mime === 'application/pdf' || str_starts_with($mime, 'text/') || str_contains($mime, 'json') || str_contains($mime, 'zip') => 'document',
            default => 'other',
        };
    }

    public static function categoryIcon(string $cat): string
    {
        return self::categories()[$cat]['icon'] ?? 'bi-file-earmark';
    }

    public static function categoryLabel(string $cat): string
    {
        return self::categories()[$cat]['title'] ?? 'ملف';
    }

    public static function toolTitle(string $cat, string $tool): string
    {
        if ($cat === 'share') {
            return 'مشاركة برابط';
        }
        $c = self::categories()[$cat] ?? null;
        return $c ? $c['title'] . ' · ' . ($c['tools'][$tool]['title'] ?? $tool) : $tool;
    }

    public static function validateFields(array $fields, array $input): array
    {
        $clean = [];
        $errors = [];

        foreach ($fields as $f) {
            $name = $f['name'];
            $label = $f['label'];
            $required = $f['required'] ?? true;
            $raw = $input[$name] ?? null;

            if ($f['type'] === 'checkbox') {
                $clean[$name] = !empty($raw) && $raw !== '0' && $raw !== 'false';
                continue;
            }

            $str = is_scalar($raw) ? trim((string) $raw) : '';
            if ($str === '') {
                if ($required && !isset($f['value'])) {
                    $errors[] = "الحقل «{$label}» مطلوب.";
                }
                $clean[$name] = $f['value'] ?? null;
                continue;
            }

            if (in_array($f['type'], ['number', 'range'], true)) {
                if (!is_numeric($str)) {
                    $errors[] = "قيمة «{$label}» يجب أن تكون رقماً.";
                    continue;
                }
                $num = (float) $str;
                if (isset($f['min']) && $num < $f['min']) {
                    $errors[] = "«{$label}» أقل من {$f['min']}.";
                }
                if (isset($f['max']) && $num > $f['max']) {
                    $errors[] = "«{$label}» أكبر من {$f['max']}.";
                }
                $clean[$name] = isset($f['step']) && $f['step'] < 1 ? $num : (int) round($num);
            } elseif ($f['type'] === 'select') {
                if (!array_key_exists($str, $f['options'])) {
                    $errors[] = "خيار غير صالح في «{$label}».";
                }
                $clean[$name] = $str;
            } else {
                if (isset($f['maxlength']) && mb_strlen($str) > $f['maxlength']) {
                    $errors[] = "«{$label}» أطول من اللازم.";
                }
                $clean[$name] = $str;
            }
        }

        return [$clean, $errors];
    }
}

