<?php
/** معالجة الصور على الخادم باستخدام مكتبة GD المدمجة في PHP. */

const IMAGE_FORMATS = [
    IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF  => 'gif', IMAGETYPE_BMP => 'bmp',
];
const IMAGE_MIMES = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'bmp' => 'image/bmp'];

function image_tool(string $tool, array $file, array $o): array
{
    if (!extension_loaded('gd')) {
        throw new ToolError('مكتبة GD غير مفعّلة في PHP. فعّل extension=gd في php.ini.');
    }
    // التحقق من أن الملف صورة حقيقية (لا نعتمد على الامتداد فقط)
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset(IMAGE_FORMATS[$info[2]])) {
        throw new ToolError('الملف ليس صورة صالحة أو أن صيغته غير مدعومة.');
    }
    [$w, $h] = $info;
    if ($w * $h > MAX_IMAGE_PIXELS) {
        throw new ToolError('أبعاد الصورة كبيرة جداً للمعالجة.');
    }
    ini_set('memory_limit', '512M');

    $srcFormat = IMAGE_FORMATS[$info[2]];
    $img = image_load($file['tmp_name'], $srcFormat);
    $outFormat = $srcFormat;
    $quality = 90;
    $suffix = $tool;

    switch ($tool) {
        case 'compress':
            $quality = $o['quality'];
            if ($o['format'] !== 'keep') {
                $outFormat = $o['format'];
            } elseif (in_array($srcFormat, ['gif', 'bmp'], true)) {
                $outFormat = 'jpg'; // لا يوجد ضغط جودة لهذه الصيغ
            }
            if ($o['max_width'] && imagesx($img) > $o['max_width']) {
                $img = image_resize($img, $o['max_width'], (int) round(imagesy($img) * $o['max_width'] / imagesx($img)));
            }
            break;

        case 'resize':
            [$nw, $nh] = [$o['width'], $o['height']];
            if (!$nw && !$nh) {
                throw new ToolError('أدخل العرض أو الارتفاع على الأقل.');
            }
            if ($o['keep_ratio'] || !$nw || !$nh) {
                $ratio = $nw && $nh ? min($nw / $w, $nh / $h) : ($nw ? $nw / $w : $nh / $h);
                [$nw, $nh] = [max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio))];
            }
            if ($nw * $nh > MAX_IMAGE_PIXELS) {
                throw new ToolError('الأبعاد المطلوبة كبيرة جداً.');
            }
            $img = image_resize($img, $nw, $nh);
            $suffix = "{$nw}x{$nh}";
            break;

        case 'crop':
            if ($o['x'] >= $w || $o['y'] >= $h) {
                throw new ToolError("نقطة البداية خارج الصورة (أبعادها {$w}×{$h}).");
            }
            $cw = min($o['width'], $w - $o['x']);
            $ch = min($o['height'], $h - $o['y']);
            $cropped = image_canvas($cw, $ch);
            imagecopy($cropped, $img, 0, 0, $o['x'], $o['y'], $cw, $ch);
            $img = $cropped;
            break;

        case 'rotate':
            if ($o['angle'] !== '0') {
                // imagerotate تدور عكس عقارب الساعة، لذلك نعكس الزاوية
                $img = imagerotate($img, 360 - (int) $o['angle'], imagecolorallocatealpha($img, 0, 0, 0, 127));
                imagesavealpha($img, true);
            }
            if ($o['flip'] !== 'none') {
                imageflip($img, $o['flip'] === 'h' ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
            }
            break;

        case 'convert':
            $outFormat = $o['format'];
            $suffix = '';
            break;

        case 'filter':
            match ($o['effect']) {
                'grayscale' => imagefilter($img, IMG_FILTER_GRAYSCALE),
                'sepia'     => imagefilter($img, IMG_FILTER_GRAYSCALE) && imagefilter($img, IMG_FILTER_COLORIZE, 90, 55, 30),
                'negate'    => imagefilter($img, IMG_FILTER_NEGATE),
                'blur'      => array_map(fn () => imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR), range(1, 8)),
                'sharpen'   => imageconvolution($img, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0),
                'brighten'  => imagefilter($img, IMG_FILTER_BRIGHTNESS, 35),
            };
            $suffix = $o['effect'];
            break;

        default:
            throw new ToolError('أداة غير معروفة.');
    }

    $base = pathinfo(clean_filename($file['name']), PATHINFO_FILENAME);
    return [
        'data' => image_encode($img, $outFormat, $quality),
        'name' => $base . ($suffix ? "-$suffix" : '') . '.' . $outFormat,
        'mime' => IMAGE_MIMES[$outFormat],
        'info' => imagesx($img) . '×' . imagesy($img) . ' بكسل',
    ];
}

function image_load(string $path, string $format): GdImage
{
    $img = match ($format) {
        'jpg'  => @imagecreatefromjpeg($path),
        'png'  => @imagecreatefrompng($path),
        'webp' => @imagecreatefromwebp($path),
        'gif'  => @imagecreatefromgif($path),
        'bmp'  => @imagecreatefrombmp($path),
    };
    if (!$img) {
        throw new ToolError('تعذّرت قراءة الصورة، قد يكون الملف تالفاً.');
    }
    if (!imageistruecolor($img)) {
        imagepalettetotruecolor($img);
    }
    // تصحيح اتجاه صور الجوال حسب بيانات EXIF
    if ($format === 'jpg' && function_exists('exif_read_data')) {
        $orientation = @exif_read_data($path)['Orientation'] ?? 1;
        $img = match ((int) $orientation) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }
    imagesavealpha($img, true);
    return $img;
}

/** لوحة فارغة شفافة بأبعاد محددة. */
function image_canvas(int $w, int $h): GdImage
{
    $c = imagecreatetruecolor($w, $h);
    imagealphablending($c, false);
    imagesavealpha($c, true);
    imagefill($c, 0, 0, imagecolorallocatealpha($c, 0, 0, 0, 127));
    return $c;
}

function image_resize(GdImage $img, int $w, int $h): GdImage
{
    $c = image_canvas($w, $h);
    imagecopyresampled($c, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
    return $c;
}

/** يحوّل الصورة إلى بيانات ثنائية بالصيغة المطلوبة. */
function image_encode(GdImage $img, string $format, int $quality): string
{
    if (in_array($format, ['jpg', 'bmp'], true)) {
        // هذه الصيغ لا تدعم الشفافية: نضع خلفية بيضاء
        $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        $img = $bg;
    }
    ob_start();
    $ok = match ($format) {
        'jpg'  => imagejpeg($img, null, $quality),
        'webp' => imagewebp($img, null, $quality),
        'png'  => (function () use ($img, $quality) {
            // جودة منخفضة في PNG = تقليل الألوان (ضغط فعّال مع خسارة بسيطة)
            if ($quality < 80) {
                imagetruecolortopalette($img, true, 256);
            }
            return imagepng($img, null, 9);
        })(),
        'gif'  => imagegif($img),
        'bmp'  => imagebmp($img),
    };
    $data = ob_get_clean();
    if (!$ok || $data === '') {
        throw new ToolError('تعذّر حفظ الصورة بالصيغة المطلوبة.');
    }
    return $data;
}

