<?php
/**
 * تعريف أنواع الملفات وأدواتها — مصدر واحد يُستخدم في:
 *  - رسم الصفحات والنماذج (tool_page.php)
 *  - التحقق على الخادم (validate_fields في api/process.php)
 *  - تمرير الإعدادات لجافاسكربت
 *
 * engine: server = PHP على الخادم | ffmpeg = ffmpeg.wasm في المتصفح | pdf = pdf-lib في المتصفح
 */

$imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
$videoExt = ['mp4', 'webm', 'mov', 'mkv', 'avi', 'm4v', '3gp'];
$audioExt = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma'];
$textExt  = ['txt', 'csv', 'json', 'md', 'html', 'xml', 'log', 'srt'];

$start = ['name' => 'start', 'label' => 'البداية (ثانية)', 'type' => 'number', 'min' => 0, 'step' => 0.1, 'value' => 0, 'media_time' => true];
$end   = ['name' => 'end', 'label' => 'النهاية (ثانية)', 'type' => 'number', 'min' => 0.1, 'step' => 0.1, 'media_time' => true];

return [
    'image' => [
        'title' => 'الصور', 'icon' => 'bi-image', 'page' => 'image.php',
        'desc'  => 'صغّر حجم صورك، غيّر أبعادها، قصّها، دوّرها، أو حوّل صيغتها.',
        'tools' => [
            'compress' => [
                'title' => 'تصغير الحجم', 'icon' => 'bi-arrows-collapse', 'engine' => 'server', 'accept' => $imageExt,
                'desc' => 'قلّل حجم الصورة مع جودة مقبولة.',
                'fields' => [
                    ['name' => 'quality', 'label' => 'الجودة', 'type' => 'range', 'min' => 10, 'max' => 95, 'value' => 70, 'suffix' => '%'],
                    ['name' => 'format', 'label' => 'صيغة الناتج', 'type' => 'select', 'value' => 'keep', 'options' => ['keep' => 'نفس الصيغة', 'jpg' => 'JPG', 'webp' => 'WebP (الأصغر)']],
                    ['name' => 'max_width', 'label' => 'أقصى عرض بالبكسل (اختياري)', 'type' => 'number', 'min' => 16, 'max' => 10000, 'required' => false, 'placeholder' => 'بدون تغيير'],
                ],
            ],
            'resize' => [
                'title' => 'تغيير الأبعاد', 'icon' => 'bi-aspect-ratio', 'engine' => 'server', 'accept' => $imageExt,
                'desc' => 'حدد العرض أو الارتفاع الجديد.',
                'fields' => [
                    ['name' => 'width', 'label' => 'العرض (px)', 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                    ['name' => 'height', 'label' => 'الارتفاع (px)', 'type' => 'number', 'min' => 1, 'max' => 10000, 'required' => false],
                    ['name' => 'keep_ratio', 'label' => 'حافظ على تناسب الأبعاد', 'type' => 'checkbox', 'value' => true],
                ],
            ],
            'crop' => [
                'title' => 'قص (Crop)', 'icon' => 'bi-crop', 'engine' => 'server', 'accept' => $imageExt, 'crop_ui' => true,
                'desc' => 'اسحب على المعاينة لتحديد المنطقة، أو اكتب القيم.',
                'fields' => [
                    ['name' => 'x', 'label' => 'من اليسار X', 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                    ['name' => 'y', 'label' => 'من الأعلى Y', 'type' => 'number', 'min' => 0, 'max' => 20000, 'value' => 0],
                    ['name' => 'width', 'label' => 'العرض', 'type' => 'number', 'min' => 1, 'max' => 20000],
                    ['name' => 'height', 'label' => 'الارتفاع', 'type' => 'number', 'min' => 1, 'max' => 20000],
                ],
            ],
            'rotate' => [
                'title' => 'تدوير وقلب', 'icon' => 'bi-arrow-clockwise', 'engine' => 'server', 'accept' => $imageExt,
                'desc' => 'دوّر الصورة أو اقلبها.',
                'fields' => [
                    ['name' => 'angle', 'label' => 'التدوير', 'type' => 'select', 'value' => '90', 'options' => ['0' => 'بدون', '90' => '90° مع عقارب الساعة', '180' => '180°', '270' => '90° عكس عقارب الساعة']],
                    ['name' => 'flip', 'label' => 'القلب', 'type' => 'select', 'value' => 'none', 'options' => ['none' => 'بدون', 'h' => 'أفقي (مرآة)', 'v' => 'عمودي']],
                ],
            ],
            'convert' => [
                'title' => 'تحويل الصيغة', 'icon' => 'bi-arrow-left-right', 'engine' => 'server', 'accept' => $imageExt,
                'desc' => 'حوّل بين JPG و PNG و WebP و GIF و BMP.',
                'fields' => [
                    ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'png', 'options' => ['jpg' => 'JPG', 'png' => 'PNG', 'webp' => 'WebP', 'gif' => 'GIF', 'bmp' => 'BMP']],
                ],
            ],
            'filter' => [
                'title' => 'فلاتر', 'icon' => 'bi-magic', 'engine' => 'server', 'accept' => $imageExt,
                'desc' => 'أبيض وأسود، سيبيا، تمويه وغيرها.',
                'fields' => [
                    ['name' => 'effect', 'label' => 'التأثير', 'type' => 'select', 'value' => 'grayscale', 'options' => ['grayscale' => 'أبيض وأسود', 'sepia' => 'سيبيا (قديم)', 'negate' => 'عكس الألوان', 'blur' => 'تمويه', 'sharpen' => 'زيادة الحدة', 'brighten' => 'زيادة الإضاءة']],
                ],
            ],
        ],
    ],

    'video' => [
        'title' => 'الفيديو', 'icon' => 'bi-camera-video', 'page' => 'video.php',
        'desc'  => 'صغّر حجم الفيديو، قصّ جزءاً منه، اقتصّ الإطار، أو حوّل صيغته — داخل متصفحك.',
        'tools' => [
            'compress' => [
                'title' => 'تصغير الحجم', 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'ضغط الفيديو إلى MP4 بحجم أصغر.',
                'fields' => [
                    ['name' => 'quality', 'label' => 'مستوى الضغط', 'type' => 'select', 'value' => '28', 'options' => ['23' => 'خفيف (جودة عالية)', '28' => 'متوسط (موصى به)', '33' => 'قوي (أصغر حجم)']],
                    ['name' => 'max_height', 'label' => 'أقصى دقة', 'type' => 'select', 'value' => '720', 'options' => ['0' => 'بدون تغيير', '1080' => '1080p', '720' => '720p', '480' => '480p', '360' => '360p']],
                ],
            ],
            'trim' => [
                'title' => 'قص المدة', 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'احتفظ بجزء محدد من الفيديو.',
                'fields' => [$start, $end, ['name' => 'fast', 'label' => 'قص سريع بدون إعادة ترميز (أقل دقة)', 'type' => 'checkbox', 'value' => true]],
            ],
            'crop' => [
                'title' => 'اقتصاص الإطار', 'icon' => 'bi-crop', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'غيّر أبعاد الإطار (مثلاً لستوري أو ريلز).',
                'fields' => [
                    ['name' => 'ratio', 'label' => 'النسبة', 'type' => 'select', 'value' => '9:16', 'options' => ['9:16' => '9:16 (ستوري / ريلز)', '1:1' => '1:1 (مربع)', '4:5' => '4:5 (منشور)', '16:9' => '16:9 (عريض)', '4:3' => '4:3']],
                ],
            ],
            'convert' => [
                'title' => 'تحويل الصيغة', 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'MP4، WebM، MOV، MKV، AVI أو GIF متحرك.',
                'fields' => [
                    ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'mp4', 'options' => ['mp4' => 'MP4', 'webm' => 'WebM', 'mov' => 'MOV', 'mkv' => 'MKV', 'avi' => 'AVI', 'gif' => 'GIF متحرك (أول 15 ثانية)']],
                ],
            ],
            'extract_audio' => [
                'title' => 'استخراج الصوت', 'icon' => 'bi-music-note', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'احفظ صوت الفيديو كملف مستقل.',
                'fields' => [
                    ['name' => 'format', 'label' => 'صيغة الصوت', 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'm4a' => 'M4A']],
                ],
            ],
            'mute' => [
                'title' => 'كتم الصوت', 'icon' => 'bi-volume-mute', 'engine' => 'ffmpeg', 'accept' => $videoExt,
                'desc' => 'أزل الصوت من الفيديو.', 'fields' => [],
            ],
        ],
    ],

    'audio' => [
        'title' => 'الصوت', 'icon' => 'bi-music-note-beamed', 'page' => 'audio.php',
        'desc'  => 'حوّل صيغة ملفاتك الصوتية، صغّر حجمها، قصّها أو عدّل مستوى الصوت.',
        'tools' => [
            'convert' => [
                'title' => 'تحويل الصيغة', 'icon' => 'bi-arrow-left-right', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                'desc' => 'MP3، WAV، OGG، M4A أو FLAC.',
                'fields' => [
                    ['name' => 'format', 'label' => 'الصيغة الجديدة', 'type' => 'select', 'value' => 'mp3', 'options' => ['mp3' => 'MP3', 'wav' => 'WAV', 'ogg' => 'OGG', 'm4a' => 'M4A', 'flac' => 'FLAC']],
                ],
            ],
            'compress' => [
                'title' => 'تصغير الحجم', 'icon' => 'bi-arrows-collapse', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                'desc' => 'خفّض معدل البت للحصول على ملف MP3 أصغر.',
                'fields' => [
                    ['name' => 'bitrate', 'label' => 'معدل البت', 'type' => 'select', 'value' => '96', 'options' => ['128' => '128k (جيد)', '96' => '96k (متوسط)', '64' => '64k (صغير)', '32' => '32k (صوت كلام)']],
                    ['name' => 'mono', 'label' => 'تحويل إلى أحادي (Mono)', 'type' => 'checkbox', 'value' => false],
                ],
            ],
            'trim' => [
                'title' => 'قص المدة', 'icon' => 'bi-scissors', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                'desc' => 'احتفظ بجزء محدد من الملف الصوتي.', 'fields' => [$start, $end],
            ],
            'volume' => [
                'title' => 'مستوى الصوت', 'icon' => 'bi-volume-up', 'engine' => 'ffmpeg', 'accept' => $audioExt,
                'desc' => 'ارفع أو اخفض الصوت، أو اجعله متوازناً.',
                'fields' => [
                    ['name' => 'level', 'label' => 'المستوى', 'type' => 'select', 'value' => '1.5', 'options' => ['0.5' => '50%', '0.75' => '75%', '1.25' => '125%', '1.5' => '150%', '2' => '200%', 'normalize' => 'موازنة تلقائية']],
                ],
            ],
        ],
    ],

    'document' => [
        'title' => 'المستندات', 'icon' => 'bi-file-earmark-text', 'page' => 'document.php',
        'desc'  => 'أدوات PDF، تحويل الملفات النصية، تنظيفها، وضغط الملفات في ZIP.',
        'tools' => [
            'pdf_merge' => [
                'title' => 'دمج PDF', 'icon' => 'bi-union', 'engine' => 'pdf', 'accept' => ['pdf'], 'multi' => true,
                'desc' => 'اجمع عدة ملفات PDF في ملف واحد بالترتيب.', 'fields' => [],
            ],
            'pdf_extract' => [
                'title' => 'استخراج صفحات', 'icon' => 'bi-file-earmark-break', 'engine' => 'pdf', 'accept' => ['pdf'],
                'desc' => 'أنشئ PDF جديداً من صفحات محددة.',
                'fields' => [['name' => 'pages', 'label' => 'الصفحات (مثال: 1-3,5)', 'type' => 'text', 'placeholder' => '1-3,5']],
            ],
            'pdf_delete' => [
                'title' => 'حذف صفحات', 'icon' => 'bi-file-earmark-x', 'engine' => 'pdf', 'accept' => ['pdf'],
                'desc' => 'احذف صفحات لا تحتاجها.',
                'fields' => [['name' => 'pages', 'label' => 'الصفحات المحذوفة (مثال: 2,4-6)', 'type' => 'text', 'placeholder' => '2,4-6']],
            ],
            'pdf_rotate' => [
                'title' => 'تدوير PDF', 'icon' => 'bi-arrow-clockwise', 'engine' => 'pdf', 'accept' => ['pdf'],
                'desc' => 'دوّر كل الصفحات أو صفحات محددة.',
                'fields' => [
                    ['name' => 'angle', 'label' => 'الزاوية', 'type' => 'select', 'value' => '90', 'options' => ['90' => '90°', '180' => '180°', '270' => '270°']],
                    ['name' => 'pages', 'label' => 'الصفحات (فارغ = الكل)', 'type' => 'text', 'required' => false, 'placeholder' => 'الكل'],
                ],
            ],
            'images_pdf' => [
                'title' => 'صور إلى PDF', 'icon' => 'bi-images', 'engine' => 'pdf', 'accept' => $imageExt, 'multi' => true,
                'desc' => 'حوّل صورة أو أكثر إلى ملف PDF.',
                'fields' => [['name' => 'size', 'label' => 'حجم الصفحة', 'type' => 'select', 'value' => 'a4', 'options' => ['a4' => 'A4', 'fit' => 'بحجم الصورة']]],
            ],
            'text_convert' => [
                'title' => 'تحويل ملف نصي', 'icon' => 'bi-filetype-json', 'engine' => 'server', 'accept' => $textExt,
                'desc' => 'CSV ⇄ JSON، نص إلى HTML، تنسيق JSON.',
                'fields' => [['name' => 'mode', 'label' => 'التحويل', 'type' => 'select', 'value' => 'csv_json', 'options' => [
                    'csv_json' => 'CSV ← إلى JSON', 'json_csv' => 'JSON ← إلى CSV', 'txt_html' => 'نص ← إلى HTML', 'json_pretty' => 'تنسيق JSON (مقروء)', 'json_minify' => 'تصغير JSON',
                ]]],
            ],
            'text_clean' => [
                'title' => 'تنظيف النص', 'icon' => 'bi-stars', 'engine' => 'server', 'accept' => $textExt,
                'desc' => 'حذف الفراغات الزائدة، الأسطر الفارغة، الترتيب والتكرار.',
                'fields' => [['name' => 'action', 'label' => 'العملية', 'type' => 'select', 'value' => 'spaces', 'options' => [
                    'spaces' => 'حذف الفراغات الزائدة', 'empty_lines' => 'حذف الأسطر الفارغة', 'unique' => 'حذف الأسطر المكررة', 'sort' => 'ترتيب الأسطر أبجدياً', 'upper' => 'أحرف إنجليزية كبيرة', 'lower' => 'أحرف إنجليزية صغيرة',
                ]]],
            ],
            'text_replace' => [
                'title' => 'بحث واستبدال', 'icon' => 'bi-search', 'engine' => 'server', 'accept' => $textExt,
                'desc' => 'استبدل كلمة أو عبارة في كامل الملف.',
                'fields' => [
                    ['name' => 'find', 'label' => 'ابحث عن', 'type' => 'text', 'maxlength' => 500],
                    ['name' => 'replace', 'label' => 'استبدل بـ', 'type' => 'text', 'required' => false, 'maxlength' => 500],
                    ['name' => 'case_sensitive', 'label' => 'مطابقة حالة الأحرف', 'type' => 'checkbox', 'value' => false],
                ],
            ],
            'text_encoding' => [
                'title' => 'إصلاح الترميز', 'icon' => 'bi-translate', 'engine' => 'server', 'accept' => $textExt,
                'desc' => 'حوّل الملفات ذات الحروف العربية المشوّهة إلى UTF-8.',
                'fields' => [['name' => 'from', 'label' => 'الترميز الأصلي', 'type' => 'select', 'value' => 'auto', 'options' => [
                    'auto' => 'تلقائي', 'CP1256' => 'Windows-1256 (عربي)', 'ISO-8859-6' => 'ISO-8859-6 (عربي)', 'UTF-16' => 'UTF-16', 'CP1252' => 'Windows-1252 (لاتيني)',
                ]]],
            ],
            'text_stats' => [
                'title' => 'إحصائيات النص', 'icon' => 'bi-bar-chart', 'engine' => 'server', 'accept' => $textExt,
                'desc' => 'عدد الكلمات والأحرف والأسطر وأكثر الكلمات تكراراً.', 'fields' => [],
            ],
            'zip' => [
                'title' => 'ضغط إلى ZIP', 'icon' => 'bi-file-zip', 'engine' => 'server', 'accept' => [], 'multi' => true,
                'desc' => 'اجمع أي ملفات في أرشيف ZIP واحد.',
                'fields' => [['name' => 'archive', 'label' => 'اسم الأرشيف', 'type' => 'text', 'required' => false, 'maxlength' => 80, 'placeholder' => 'files']],
            ],
        ],
    ],
];

