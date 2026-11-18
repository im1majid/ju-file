<?php

namespace App\Services\FileProcessing;

use App\Services\ToolCatalog;
use GdImage;
use Illuminate\Http\UploadedFile;

class ImageProcessor
{
    private const FORMATS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_BMP  => 'bmp',
    ];

    private const MIMES = [
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
    ];

    public function process(string $tool, UploadedFile $file, array $o): array
    {
        if (!extension_loaded('gd')) {
            throw new ToolException('مكتبة GD غير مفعّلة في PHP. فعّل extension=gd في php.ini.');
        }

        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if (!$info || !isset(self::FORMATS[$info[2]])) {
            throw new ToolException('الملف ليس صورة صالحة أو أن صيغته غير مدعومة.');
        }

        [$w, $h] = $info;
        if ($w * $h > ToolCatalog::MAX_IMAGE_PIXELS) {
            throw new ToolException('أبعاد الصورة كبيرة جداً للمعالجة.');
        }
        ini_set('memory_limit', '512M');

        $srcFormat = self::FORMATS[$info[2]];
        $img = $this->load($path, $srcFormat);
        $outFormat = $srcFormat;
        $quality = 90;
        $suffix = $tool;

        switch ($tool) {
            case 'compress':
                $quality = $o['quality'];
                if ($o['format'] !== 'keep') {
                    $outFormat = $o['format'];
                } elseif (in_array($srcFormat, ['gif', 'bmp'], true)) {
                    $outFormat = 'jpg';
                }
                if ($o['max_width'] && imagesx($img) > $o['max_width']) {
                    $img = $this->resize($img, $o['max_width'], (int) round(imagesy($img) * $o['max_width'] / imagesx($img)));
                }
                break;

            case 'resize':
                [$nw, $nh] = [$o['width'], $o['height']];
                if ((!$nw || $nw < 1) && (!$nh || $nh < 1)) {
                    throw new ToolException('أدخل العرض أو الارتفاع بقيمة صحيحة أكبر من صفر.');
                }
                if ($o['keep_ratio'] || !$nw || !$nh) {
                    $axis = $o['changed_axis'] ?? 'width';
                    if ($nw && $nh) {
                        // إذا تم التعديل مع الحفاظ على التناسب، نحسب البعد الآخر بناءً على المحور المعدّل وأبعاد الصورة الأصلية
                        if ($axis === 'height') {
                            $nw = max(1, (int) round($nh * ($w / $h)));
                        } else {
                            $nh = max(1, (int) round($nw * ($h / $w)));
                        }
                    } elseif ($nw) {
                        $nh = max(1, (int) round($nw * ($h / $w)));
                    } else {
                        $nw = max(1, (int) round($nh * ($w / $h)));
                    }
                }
                if ($nw * $nh > ToolCatalog::MAX_IMAGE_PIXELS) {
                    throw new ToolException('الأبعاد المطلوبة كبيرة جداً.');
                }
                $img = $this->resize($img, $nw, $nh);
                $suffix = "{$nw}x{$nh}";
                break;

            case 'crop':
                if ($o['x'] >= $w || $o['y'] >= $h) {
                    throw new ToolException("نقطة البداية خارج الصورة (أبعادها {$w}×{$h}).");
                }
                $cw = min($o['width'], $w - $o['x']);
                $ch = min($o['height'], $h - $o['y']);
                $cropped = $this->canvas($cw, $ch);
                imagecopy($cropped, $img, 0, 0, $o['x'], $o['y'], $cw, $ch);
                $img = $cropped;
                break;

            case 'rotate':
                if ($o['angle'] !== '0') {
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
                $intensity = max(0, min(100, (int) ($o['intensity'] ?? 100)));
                if ($intensity > 0) {
                    $img = $this->applyFilter($img, $o['effect'], $intensity);
                }
                $suffix = $o['effect'] . '-' . $intensity;
                break;

            case 'text':
                $this->applyText($img, $o);
                $suffix = 'text';
                break;

            default:
                throw new ToolException('أداة غير معروفة.');
        }

        $base = pathinfo(ToolCatalog::cleanFilename($file->getClientOriginalName()), PATHINFO_FILENAME);

        return [
            'data' => $this->encode($img, $outFormat, $quality),
            'name' => $base . ($suffix ? "-{$suffix}" : '') . ".{$outFormat}",
            'mime' => self::MIMES[$outFormat],
            'info' => imagesx($img) . '×' . imagesy($img) . ' بكسل',
        ];
    }

    private function applyFilter(GdImage $img, string $effect, int $intensity): GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);

        if ($effect === 'brighten') {
            imagefilter($img, IMG_FILTER_BRIGHTNESS, (int) round(65 * ($intensity / 100)));
            return $img;
        }

        if ($effect === 'blur') {
            $passes = max(1, (int) round(14 * ($intensity / 100)));
            for ($i = 0; $i < $passes; $i++) {
                imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
            }
            return $img;
        }

        // نسخ الصورة الأصلية للمزج الدقيق عند اختيار شدة أقل من 100%
        $orig = null;
        if ($intensity < 100) {
            $orig = $this->canvas($w, $h);
            imagecopy($orig, $img, 0, 0, 0, 0, $w, $h);
        }

        match ($effect) {
            'grayscale' => imagefilter($img, IMG_FILTER_GRAYSCALE),
            'sepia'     => imagefilter($img, IMG_FILTER_GRAYSCALE) && imagefilter($img, IMG_FILTER_COLORIZE, 90, 55, 30),
            'negate'    => imagefilter($img, IMG_FILTER_NEGATE),
            'sharpen'   => imageconvolution($img, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0),
        };

        if ($orig === null) {
            return $img;
        }

        // مزج الفلتر مع الصورة الأصلية بنسبة الشدة مع الحفاظ على شفافية الألفا
        $alphaOverlay = (int) round(127 * (1 - $intensity / 100));
        imagealphablending($img, false);
        imagefilter($img, IMG_FILTER_COLORIZE, 0, 0, 0, $alphaOverlay);
        imagealphablending($orig, true);
        imagesavealpha($orig, true);
        imagecopy($orig, $img, 0, 0, 0, 0, $w, $h);

        return $orig;
    }

    private function applyText(GdImage $img, array $o): void
    {
        $text = trim((string) ($o['text'] ?? ''));
        if ($text === '') {
            throw new ToolException('أدخل النص المطلوب إضافته.');
        }

        $fontMap = [
            'ibmplex'      => public_path('assets/fonts/ibmplex.ttf'),
            'ibmplex-bold' => public_path('assets/fonts/ibmplex-bold.ttf'),
            'amiri'        => public_path('assets/fonts/amiri.ttf'),
            'amiri-bold'   => public_path('assets/fonts/amiri-bold.ttf'),
        ];
        $fontPath = $fontMap[$o['font'] ?? 'ibmplex'] ?? $fontMap['ibmplex'];
        if (!is_file($fontPath)) {
            throw new ToolException('ملف الخط غير موجود على الخادم.');
        }

        $fontSizePx = max(8, min(300, (int) ($o['font_size'] ?? 42)));
        // تحويل حجم الخط من px إلى pt التي تتوقعها imagettftext (1pt ≈ 0.75px في 96dpi)
        $ptSize = $fontSizePx * 0.75;

        $hex = ltrim((string) ($o['color'] ?? '#ffffff'), '#');
        [$r, $g, $b] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        imagealphablending($img, true);
        $color = imagecolorallocate($img, $r, $g, $b);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 80);

        $rawLines = preg_split('/\r\n|\r|\n/', $text);
        $shapedLines = array_map(fn ($ln) => ArabicTextShaper::shape($ln), $rawLines);

        // قياس عرض الصندوق الكلي لمحاذاة الأسطر العربية يميناً داخل منطقة النص
        $boxes = [];
        $maxW = 0;
        foreach ($shapedLines as $ln) {
            $bb = imagettfbbox($ptSize, 0, $fontPath, $ln === '' ? ' ' : $ln);
            $lw = abs($bb[2] - $bb[0]);
            $minX = min($bb[0], $bb[6]);
            $minY = min($bb[5], $bb[7]);
            $maxW = max($maxW, $lw);
            $boxes[] = ['line' => $ln, 'w' => $lw, 'minX' => $minX, 'minY' => $minY];
        }

        $x = max(0, min(imagesx($img) - 1, (int) ($o['x'] ?? 0)));
        $y = max(0, min(imagesy($img) - 1, (int) ($o['y'] ?? 0)));
        $lineHeight = (int) round($fontSizePx * 1.35);
        $isArabic = (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $text);

        foreach ($boxes as $idx => $b) {
            if ($b['line'] === '') {
                continue;
            }
            $alignOffset = $isArabic ? ($maxW - $b['w']) : 0;
            $drawX = $x + $alignOffset - $b['minX'];
            $drawY = $y + ($idx * $lineHeight) - $b['minY'];
            imagettftext($img, $ptSize, 0, $drawX + 1, $drawY + 2, $shadow, $fontPath, $b['line']);
            imagettftext($img, $ptSize, 0, $drawX, $drawY, $color, $fontPath, $b['line']);
        }
    }

    private function load(string $path, string $format): GdImage
    {
        $img = match ($format) {
            'jpg'  => @imagecreatefromjpeg($path),
            'png'  => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            'gif'  => @imagecreatefromgif($path),
            'bmp'  => @imagecreatefrombmp($path),
        };
        if (!$img) {
            throw new ToolException('تعذّرت قراءة الصورة، قد يكون الملف تالفاً.');
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
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

    private function canvas(int $w, int $h): GdImage
    {
        $c = imagecreatetruecolor($w, $h);
        imagealphablending($c, false);
        imagesavealpha($c, true);
        imagefill($c, 0, 0, imagecolorallocatealpha($c, 0, 0, 0, 127));
        return $c;
    }

    private function resize(GdImage $img, int $w, int $h): GdImage
    {
        $c = $this->canvas($w, $h);
        imagecopyresampled($c, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
        return $c;
    }

    private function encode(GdImage $img, string $format, int $quality): string
    {
        if (in_array($format, ['jpg', 'bmp'], true)) {
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
            throw new ToolException('تعذّر حفظ الصورة بالصيغة المطلوبة.');
        }
        return $data;
    }
}

