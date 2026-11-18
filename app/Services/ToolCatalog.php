<?php

namespace App\Services;

class ToolCatalog
{
    public const MAX_GUEST_BYTES  = 15 * 1024 * 1024;  // 15 MB للزوار
    public const MAX_USER_BYTES   = 100 * 1024 * 1024; // 100 MB للمسجلين
    public const MAX_UPLOAD_BYTES = self::MAX_USER_BYTES;
    public const MAX_IMAGE_PIXELS = 45_000_000;

    public static function maxUploadBytes(bool $loggedIn): int
    {
        return $loggedIn ? self::MAX_USER_BYTES : self::MAX_GUEST_BYTES;
    }

    public static function categories(): array
    {
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
        $videoExt = ['mp4', 'webm', 'mov', 'mkv', 'avi', 'm4v', '3gp'];
        $audioExt = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma'];
        $textExt  = ['txt', 'csv', 'json', 'md', 'html', 'xml', 'log', 'srt'];

        $start = ['name' => 'start', 'label' => __('البداية (ثانية)'), 'type' => 'number', 'min' => 0, 'step' => 0.1, 'value' => 0, 'media_time' => true];
        $end   = ['name' => 'end', 'label' => __('النهاية (ثانية)'), 'type' => 'number', 'min' => 0.1, 'step' => 0.1, 'value' => null, 'media_time' => true];

        return [
            'image' => [
                'title' => __('الصور'), 'icon' => 'bi-image', 'route' => 'tools.category',
                'desc'  => __('صغّر حجم صورك، غيّر أبعادها، قصّها، دوّرها، أو حوّل صيغتها.'),
                'tools' => [
                    'compress' => [
                        'title' => __('تصغير حجم الصورة'), 'icon' => 'bi-arrows-collapse', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => __('قلّل حجم الصورة مع الحفاظ على جودة ممتازة.'),
                        'fields' => [
                            ['name' => 'quality', 'label' => __('الجودة'), 'type' => 'range', 'min' => 10, 'max' => 95, 'value' => 70, 'suffix' => '%'],
                            ['name' => 'format', 'label' => __('صيغة الناتج'), 'type' => 'select', 'value' => 'keep', 'options' => ['keep' => __('نفس الصيغة'), 'jpg' => 'JPG', 'webp' => __('WebP (الأصغر)')]],
                            ['name' => 'max_width', 'label' => __('أقصى عرض بالبكسل (اختياري)'), 'type' => 'number', 'min' => 16, 'max' => 10000, 'required' => false, 'placeholder' => __('بدون تغيير')],
                        ],
                    ],
                    'resize' => [
                        'title' => __('تغيير مقاس الصورة'), 'icon' => 'bi-aspect-ratio', 'engine' => 'server', 'accept' => $imageExt, 'resize_ui' => true,
                        'desc' => __('حدّد العرض أو الارتفاع الجديد بالبكسل.'),
                        'fields' => [
                            ['name' => 'width', 'label' => __('العرض (px)'), 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                            ['name' => 'height', 'label' => __('الارتفاع (px)'), 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                            ['name' => 'keep_ratio', 'label' => __('الحفاظ على تناسب الأبعاد'), 'type' => 'checkbox', 'value' => true],
                            ['name' => 'keep_existing', 'label' => __('الاحتفاظ بنفس المقاسات عند تغيير الصورة'), 'type' => 'checkbox', 'value' => false],
                        ],
                    ],
                    'crop' => [
                        'title' => __('قص الصورة'), 'icon' => 'bi-crop', 'engine' => 'server', 'accept' => $imageExt, 'crop_ui' => true,
                        'desc' => __('اسحب على الصورة لتحديد الجزء المطلوب قصّه.'),
                        'fields' => [
                            ['name' => 'x', 'label' => __('من اليسار X'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'y', 'label' => __('من الأعلى Y'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'width', 'label' => __('العرض'), 'type' => 'number', 'min' => 1, 'max' => 20000],
                            ['name' => 'height', 'label' => __('الارتفاع'), 'type' => 'number', 'min' => 1, 'max' => 20000],
                        ],
                    ],
                    'rotate' => [
                        'title' => __('تدوير وقلب الصورة'), 'icon' => 'bi-arrow-clockwise', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => __('دوّر الصورة 90° أو 180° أو اقلبها أفقياً وعمودياً.'),
                        'fields' => [
                            ['name' => 'angle', 'label' => __('التدوير'), 'type' => 'select', 'value' => '90', 'options' => ['0' => __('بدون'), '90' => __('90° مع عقارب الساعة'), '180' => '180°', '270' => __('90° عكس عقارب الساعة')]],
                            ['name' => 'flip', 'label' => __('القلب'), 'type' => 'select', 'value' => 'none', 'options' => ['none' => __('بدون'), 'h' => __('أفقي (مرآة)'), 'v' => __('عمودي')]],
                        ],
                    ],
                    'convert' => [
                        'title' => __('تحويل صيغة الصورة'), 'icon' => 'bi-arrow-left-right', 'engine' => 'server', 'accept' => $imageExt,
                        'desc' => __('حوّل بين JPG و PNG و WebP و GIF و BMP.'),
                        'fields' => [
                            ['name' => 'format', 'label' => __('الصيغة الجديدة'), 'type' => 'select', 'value' => 'png', 'options' => ['jpg' => 'JPG', 'png' => 'PNG', 'webp' => 'WebP', 'gif' => 'GIF', 'bmp' => 'BMP']],
                        ],
                    ],
                    'filter' => [
                        'title' => __('فلاتر وتأثيرات لونية'), 'icon' => 'bi-magic', 'engine' => 'server', 'accept' => $imageExt, 'filter_ui' => true,
                        'desc' => __('أبيض وأسود، سيبيا، تمويه، وزيادة الحدة أو الإضاءة مع تحكم بالشدة.'),
                        'fields' => [
                            ['name' => 'effect', 'label' => __('التأثير'), 'type' => 'select', 'value' => 'grayscale', 'options' => ['grayscale' => __('أبيض وأسود'), 'sepia' => __('سيبيا (قديم)'), 'negate' => __('عكس الألوان'), 'blur' => __('تمويه'), 'sharpen' => __('زيادة الحدة'), 'brighten' => __('زيادة الإضاءة')]],
                            ['name' => 'intensity', 'label' => __('شدة التأثير'), 'type' => 'range', 'min' => 0, 'max' => 100, 'value' => 100, 'suffix' => '%'],
                        ],
                    ],
                    'text' => [
                        'title' => __('إضافة نص على الصورة'), 'icon' => 'bi-fonts', 'engine' => 'server', 'accept' => $imageExt, 'text_ui' => true,
                        'desc' => __('أضف نصاً عربياً أو إنجليزياً فوق الصورة واختر الخط واللون واسحبه للموضع المطلوب.'),
                        'fields' => [
                            ['name' => 'text', 'label' => __('النص'), 'type' => 'textarea', 'maxlength' => 500, 'placeholder' => __('اكتب النص هنا...')],
                            ['name' => 'font', 'label' => __('الخط'), 'type' => 'select', 'value' => 'ibmplex', 'options' => [
                                'ibmplex'      => __('IBM Plex Arabic (حديث)'),
                                'ibmplex-bold' => __('IBM Plex Bold (عريض)'),
                                'amiri'        => __('Amiri (نسخ كلاسيكي)'),
                                'amiri-bold'   => __('Amiri Bold (نسخ عريض)'),
                            ]],
                            ['name' => 'font_size', 'label' => __('حجم الخط (px)'), 'type' => 'number', 'min' => 8, 'max' => 300, 'value' => 42],
                            ['name' => 'color', 'label' => __('لون النص'), 'type' => 'color', 'value' => '#ffffff'],
                            ['name' => 'x', 'label' => __('الموضع الأفقي X'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 40],
                            ['name' => 'y', 'label' => __('الموضع العمودي Y'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 40],
                        ],
                    ],
                ],
            ],

            'video' => [
                'title' => __('الفيديو'), 'icon' => 'bi-camera-video', 'route' => 'tools.category',
                'desc'  => __('صغّر حجم الفيديو، قصّ جزءاً منه، اضبط أبعاده، أو حوّل صيغته — داخل متصفحك.'),
                'tools' => [
                    'compress' => [
                        'title' => __('ضغط وتصغير الفيديو'), 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => __('ضغط الفيديو إلى MP4 بحجم أصغر.'),
                        'fields' => [
                            ['name' => 'quality', 'label' => __('مستوى الضغط'), 'type' => 'select', 'value' => '28', 'options' => ['23' => __('خفيف (جودة عالية)'), '28' => __('متوسط (موصى به)'), '33' => __('قوي (أصغر حجم)')]],
                            ['name' => 'max_height', 'label' => __('أقصى دقة'), 'type' => 'select', 'value' => '720', 'options' => ['0' => __('بدون تغيير'), '1080' => '1080p', '720' => '720p', '480' => '480p', '360' => '360p']],
                        ],
                    ],
                    'trim' => [
                        'title' => __('قص جزء من الفيديو'), 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $videoExt, 'trim_ui' => true,
                        'desc' => __('اسحب مقبضي البداية والنهاية لتحديد المقطع المطلوب.'),
                        'fields' => [$start, $end, ['name' => 'fast', 'label' => __('قص سريع بدون إعادة ترميز (أقل دقة)'), 'type' => 'checkbox', 'value' => true]],
                    ],
                    'crop' => [
                        'title' => __('قص أبعاد الفيديو'), 'icon' => 'bi-crop', 'engine' => 'ffmpeg', 'accept' => $videoExt, 'crop_ui' => true,
                        'desc' => __('اسحب على الفيديو لتحديد الإطار المطلوب قصّه أو اختر نسبة جاهزة.'),
                        'fields' => [
                            ['name' => 'x', 'label' => __('من اليسار X'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'y', 'label' => __('من الأعلى Y'), 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                            ['name' => 'width', 'label' => __('العرض'), 'type' => 'number', 'min' => 2, 'max' => 20000],
                            ['name' => 'height', 'label' => __('الارتفاع'), 'type' => 'number', 'min' => 2, 'max' => 20000],
                        ],
                    ],
                    'convert' => [
                        'title' => __('تحويل صيغة الفيديو'), 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => __('MP4، WebM، MOV، MKV، AVI أو GIF متحرك.'),
                        'fields' => [
                            ['name' => 'format', 'label' => __('الصيغة الجديدة'), 'type' => 'select', 'value' => 'mp4', 'options' => ['mp4' => 'MP4', 'webm' => 'WebM', 'mov' => 'MOV', 'mkv' => 'MKV', 'avi' => 'AVI', 'gif' => __('GIF متحرك (أول 15 ثانية)')]],
                        ],
                    ],
                    'extract_audio' => [
                        'title' => __('استخراج الصوت فقط'), 'icon' => 'bi-music-note', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => __('احفظ صوت الفيديو كملف صوتي مستقل.'),
                        'fields' => [
                            ['name' => 'format', 'label' => __('صيغة الصوت'), 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'm4a' => 'M4A']],
                        ],
                    ],
                    'mute' => [
                        'title' => __('إزالة الصوت من الفيديو'), 'icon' => 'bi-volume-mute', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                        'desc' => __('احذف المسار الصوتي واجعل الفيديو صامتاً.'), 'fields' => [],
                    ],
                ],
            ],

            'audio' => [
                'title' => __('الصوت'), 'icon' => 'bi-music-note-beamed', 'route' => 'tools.category',
                'desc'  => __('حوّل صيغة ملفاتك الصوتية، صغّر حجمها، قصّها أو عدّل مستوى الصوت.'),
                'tools' => [
                    'convert' => [
                        'title' => __('تحويل صيغة الصوت'), 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => __('MP3، WAV، OGG، M4A أو FLAC.'),
                        'fields' => [
                            ['name' => 'format', 'label' => __('الصيغة الجديدة'), 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'ogg' => 'OGG', 'm4a' => 'M4A', 'flac' => 'FLAC']],
                        ],
                    ],
                    'compress' => [
                        'title' => __('ضغط وتصغير الصوت'), 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => __('خفّض معدل البت للحصول على ملف MP3 أصغر.'),
                        'fields' => [
                            ['name' => 'bitrate', 'label' => __('معدل البت'), 'type' => 'select', 'value' => '96', 'options' => ['128' => __('128k (جيد)'), '96' => __('96k (متوسط)'), '64' => __('64k (صغير)'), '32' => __('32k (صوت كلام)')]],
                            ['name' => 'mono', 'label' => __('تحويل إلى أحادي (Mono)'), 'type' => 'checkbox', 'value' => false],
                        ],
                    ],
                    'trim' => [
                        'title' => __('قص مقطع صوتي'), 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $audioExt, 'trim_ui' => true,
                        'desc' => __('اسحب مقبضي البداية والنهاية لتحديد المقطع الصوتي.'), 'fields' => [$start, $end],
                    ],
                    'volume' => [
                        'title' => __('رفع أو خفض الصوت'), 'icon' => 'bi-volume-up', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                        'desc' => __('ارفع أو اخفض مستوى الصوت، أو وازنه تلقائياً.'),
                        'fields' => [
                            ['name' => 'level', 'label' => __('المستوى'), 'type' => 'select', 'value' => '1.5', 'options' => ['0.5' => '50%', '0.75' => '75%', '1.25' => '125%', '1.5' => '150%', '2' => '200%', 'normalize' => __('موازنة تلقائية')]],
                        ],
                    ],
                ],
            ],

            'document' => [
                'title' => __('المستندات'), 'icon' => 'bi-file-earmark-text', 'route' => 'tools.category',
                'desc'  => __('أدوات PDF، تحويل الملفات النصية، تنظيفها، وضغط الملفات في ZIP.'),
                'tools' => [
                    'pdf_merge' => [
                        'title' => __('دمج ملفات PDF'), 'icon' => 'bi-union', 'engine' => 'pdf', 'accept' => ['pdf'], 'multi' => true,
                        'desc' => __('اجمع عدة ملفات PDF في ملف واحد بالترتيب.'), 'fields' => [],
                    ],
                    'pdf_extract' => [
                        'title' => __('استخراج صفحات PDF'), 'icon' => 'bi-file-earmark-break', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => __('أنشئ ملف PDF جديداً من صفحات محددة.'),
                        'fields' => [['name' => 'pages', 'label' => __('نطاق الصفحات (مثال: 1-5 أو 1,3,5)'), 'type' => 'text', 'page_range' => true, 'placeholder' => __('1-5 أو 1,3,5')]],
                    ],
                    'pdf_delete' => [
                        'title' => __('حذف صفحات من PDF'), 'icon' => 'bi-file-earmark-x', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => __('احذف صفحات لا تحتاجها من ملف PDF.'),
                        'fields' => [['name' => 'pages', 'label' => __('نطاق الصفحات المحذوفة (مثال: 2,4-6 أو 1-3)'), 'type' => 'text', 'page_range' => true, 'placeholder' => __('2,4-6 أو 1-3')]],
                    ],
                    'pdf_rotate' => [
                        'title' => __('تدوير صفحات PDF'), 'icon' => 'bi-arrow-clockwise', 'engine' => 'pdf', 'accept' => ['pdf'],
                        'desc' => __('دوّر كل الصفحات أو نطاقاً محدداً من الصفحات.'),
                        'fields' => [
                            ['name' => 'angle', 'label' => __('الزاوية'), 'type' => 'select', 'value' => '90', 'options' => ['90' => '90°', '180' => '180°', '270' => '270°']],
                            ['name' => 'pages', 'label' => __('نطاق الصفحات (مثال: 1-5 أو 1,3,5 أو الكل)'), 'type' => 'text', 'page_range' => true, 'required' => false, 'placeholder' => __('1-5 أو 1,3,5 أو الكل')],
                        ],
                    ],
                    'images_pdf' => [
                        'title' => __('تحويل الصور إلى PDF'), 'icon' => 'bi-images', 'engine' => 'pdf', 'accept' => $imageExt, 'multi' => true,
                        'desc' => __('اجمع صورة أو أكثر في ملف PDF واحد.'),
                        'fields' => [['name' => 'size', 'label' => __('حجم الصفحة'), 'type' => 'select', 'value' => 'a4', 'options' => ['a4' => 'A4', 'fit' => __('بحجم الصورة')]]],
                    ],
                    'text_convert' => [
                        'title' => __('تحويل صيغ النصوص (CSV/JSON)'), 'icon' => 'bi-filetype-json', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => __('CSV ⇄ JSON، نص إلى HTML، وتنسيق JSON.'),
                        'fields' => [['name' => 'mode', 'label' => __('التحويل'), 'type' => 'select', 'value' => 'csv_json', 'options' => [
                            'csv_json' => __('CSV ← إلى JSON'), 'json_csv' => __('JSON ← إلى CSV'), 'txt_html' => __('نص ← إلى HTML'), 'json_pretty' => __('تنسيق JSON (مقروء)'), 'json_minify' => __('تصغير JSON'),
                        ]]],
                    ],
                    'text_clean' => [
                        'title' => __('تنظيف وترتيب النصوص'), 'icon' => 'bi-stars', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => __('حذف الفراغات الزائدة، الأسطر الفارغة، الترتيب والتكرار.'),
                        'fields' => [['name' => 'action', 'label' => __('العملية'), 'type' => 'select', 'value' => 'spaces', 'options' => [
                            'spaces' => __('حذف الفراغات الزائدة'), 'empty_lines' => __('حذف الأسطر الفارغة'), 'unique' => __('حذف الأسطر المكررة'), 'sort' => __('ترتيب الأسطر أبجدياً'), 'upper' => __('أحرف إنجليزية كبيرة'), 'lower' => __('أحرف إنجليزية صغيرة'),
                        ]]],
                    ],
                    'text_replace' => [
                        'title' => __('بحث واستبدال في النص'), 'icon' => 'bi-search', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => __('استبدل كلمة أو عبارة في كامل الملف.'),
                        'fields' => [
                            ['name' => 'find', 'label' => __('ابحث عن'), 'type' => 'text', 'maxlength' => 500],
                            ['name' => 'replace', 'label' => __('استبدل بـ'), 'type' => 'text', 'required' => false, 'maxlength' => 500],
                            ['name' => 'case_sensitive', 'label' => __('مطابقة حالة الأحرف'), 'type' => 'checkbox', 'value' => false],
                        ],
                    ],
                    'text_encoding' => [
                        'title' => __('إصلاح حروف العربي المشوّهة'), 'icon' => 'bi-translate', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => __('حوّل الملفات ذات الترميز القديم إلى UTF-8 مقروء.'),
                        'fields' => [['name' => 'from', 'label' => __('الترميز الأصلي'), 'type' => 'select', 'value' => 'auto', 'options' => [
                            'auto' => __('تلقائي'), 'CP1256' => __('Windows-1256 (عربي)'), 'ISO-8859-6' => __('ISO-8859-6 (عربي)'), 'UTF-16' => 'UTF-16', 'CP1252' => __('Windows-1252 (لاتيني)'),
                        ]]],
                    ],
                    'text_stats' => [
                        'title' => __('عدّ الكلمات والأحرف'), 'icon' => 'bi-bar-chart', 'engine' => 'server', 'accept' => $textExt,
                        'desc' => __('إحصائيات الكلمات والأحرف والأسطر ووقت القراءة.'), 'fields' => [],
                    ],
                    'zip' => [
                        'title' => __('ضغط الملفات في ZIP'), 'icon' => 'bi-file-zip', 'engine' => 'server', 'accept' => [], 'multi' => true,
                        'desc' => __('اجمع عدة ملفات في أرشيف ZIP واحد.'),
                        'fields' => [['name' => 'archive', 'label' => __('اسم الأرشيف'), 'type' => 'text', 'required' => false, 'maxlength' => 80, 'placeholder' => 'files']],
                    ],
                ],
            ],
        ];
    }

    public static function expiryOptions(bool $loggedIn): array
    {
        $opts = ['1' => __('ساعة واحدة'), '24' => __('24 ساعة'), '168' => __('7 أيام')];
        if ($loggedIn) {
            $opts += ['720' => __('30 يوماً')];
        }
        return $opts;
    }

    public static function formatBytes(int $bytes): string
    {
        $units = [__('بايت'), 'KB', 'MB', 'GB'];
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
        return self::categories()[$cat]['title'] ?? __('ملف');
    }

    public static function toolTitle(string $cat, string $tool): string
    {
        if ($cat === 'share') {
            return __('مشاركة برابط');
        }
        $c = self::categories()[$cat] ?? null;
        return $c ? $c['title'] . ' · ' . ($c['tools'][$tool]['title'] ?? $tool) : $tool;
    }

    public static function validateFields(array $fields, array $input, ?int $totalPages = null): array
    {
        $clean = [];
        $errors = [];
        $fieldErrors = [];

        $addError = function (string $name, string $msg) use (&$errors, &$fieldErrors): void {
            $errors[] = $msg;
            $fieldErrors[$name] = $fieldErrors[$name] ?? $msg;
        };

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
                    $addError($name, __('الحقل «:label» مطلوب.', ['label' => $label]));
                }
                $clean[$name] = $f['value'] ?? null;
                continue;
            }

            if ($f['type'] === 'color') {
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $str)) {
                    $addError($name, __('صيغة اللون في «:label» غير صالحة.', ['label' => $label]));
                }
                $clean[$name] = $str;
            } elseif (in_array($f['type'], ['number', 'range'], true)) {
                if (!is_numeric($str)) {
                    $addError($name, __('قيمة «:label» يجب أن تكون رقماً.', ['label' => $label]));
                    continue;
                }
                $num = (float) $str;
                if (isset($f['min']) && $num < $f['min']) {
                    $addError($name, __('«:label» أقل من :min.', ['label' => $label, 'min' => $f['min']]));
                }
                if (isset($f['max']) && $num > $f['max']) {
                    $addError($name, __('«:label» أكبر من :max.', ['label' => $label, 'max' => $f['max']]));
                }
                $clean[$name] = isset($f['step']) && $f['step'] < 1 ? $num : (int) round($num);
            } elseif ($f['type'] === 'select') {
                if (!array_key_exists($str, $f['options'])) {
                    $addError($name, __('خيار غير صالح في «:label».', ['label' => $label]));
                }
                $clean[$name] = $str;
            } else {
                if (isset($f['maxlength']) && mb_strlen($str) > $f['maxlength']) {
                    $addError($name, __('«:label» أطول من اللازم.', ['label' => $label]));
                }
                if (!empty($f['page_range'])) {
                    if ($err = self::validatePageRange($str, $totalPages)) {
                        $addError($name, $err);
                    }
                }
                $clean[$name] = $str;
            }
        }

        return [$clean, $errors, $fieldErrors];
    }

    public static function validatePageRange(string $spec, ?int $totalPages = null): ?string
    {
        $normalized = mb_strtolower(trim($spec));
        if ($normalized === '' || in_array($normalized, ['الكل', 'كل', 'all'], true)) {
            return null;
        }

        // تحويل الأرقام العربية إلى إنجليزية إن وُجدت
        $spec = strtr($spec, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','،'=>',']);
        $parts = array_filter(array_map('trim', explode(',', $spec)), fn ($p) => $p !== '');
        if (!$parts) {
            return __('نطاق الصفحات غير صالح. أدخل أرقاماً مثل 1-5 أو 1,3,5 أو «الكل».');
        }

        foreach ($parts as $p) {
            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $p, $m)) {
                $a = (int) $m[1];
                $b = (int) $m[2];
                if ($a < 1 || $b < 1) {
                    return __('أرقام الصفحات يجب أن تبدأ من 1 فأكثر.');
                }
                if ($a > $b) {
                    return __('النطاق «:range» غير صحيح: رقم البداية أكبر من النهاية.', ['range' => $p]);
                }
                if ($totalPages !== null && $b > $totalPages) {
                    return __('الصفحة :page خارج حدود المستند (إجمالي الصفحات: :total).', ['page' => $b, 'total' => $totalPages]);
                }
            } elseif (preg_match('/^\d+$/', $p)) {
                $n = (int) $p;
                if ($n < 1) {
                    return __('أرقام الصفحات يجب أن تبدأ من 1 فأكثر.');
                }
                if ($totalPages !== null && $n > $totalPages) {
                    return __('الصفحة :page خارج حدود المستند (إجمالي الصفحات: :total).', ['page' => $n, 'total' => $totalPages]);
                }
            } else {
                return __('صيغة النطاق «:part» غير صالحة. استخدم أرقاماً مثل: 1-5 أو 1,3,5 أو «الكل».', ['part' => $p]);
            }
        }

        return null;
    }
}
