<?php

namespace App\Services\FileProcessing;

/**
 * تشكيل ورصف النصوص العربية (RTL + ربط الحروف + اللام ألف + ثنائي الاتجاه BiDi)
 * لطباعة صحيحة عبر مكتبة GD (imagettftext).
 */
class ArabicTextShaper
{
    /** @var array<string, array{0: string, 1: ?string, 2: ?string, 3: string}> [isolated, initial, medial, final] */
    private const GLYPHS = [
        "\u{0621}" => ["\u{FE80}", null, null, "\u{FE80}"], // ء
        "\u{0622}" => ["\u{FE81}", null, null, "\u{FE82}"], // آ
        "\u{0623}" => ["\u{FE83}", null, null, "\u{FE84}"], // أ
        "\u{0624}" => ["\u{FE85}", null, null, "\u{FE86}"], // ؤ
        "\u{0625}" => ["\u{FE87}", null, null, "\u{FE88}"], // إ
        "\u{0626}" => ["\u{FE89}", "\u{FE8B}", "\u{FE8C}", "\u{FE8A}"], // ئ
        "\u{0627}" => ["\u{FE8D}", null, null, "\u{FE8E}"], // ا
        "\u{0628}" => ["\u{FE8F}", "\u{FE91}", "\u{FE92}", "\u{FE90}"], // ب
        "\u{0629}" => ["\u{FE93}", null, null, "\u{FE94}"], // ة
        "\u{062A}" => ["\u{FE95}", "\u{FE97}", "\u{FE98}", "\u{FE96}"], // ت
        "\u{062B}" => ["\u{FE99}", "\u{FE9B}", "\u{FE9C}", "\u{FE9A}"], // ث
        "\u{062C}" => ["\u{FE9D}", "\u{FE9F}", "\u{FEA0}", "\u{FE9E}"], // ج
        "\u{062D}" => ["\u{FEA1}", "\u{FEA3}", "\u{FEA4}", "\u{FEA2}"], // ح
        "\u{062E}" => ["\u{FEA5}", "\u{FEA7}", "\u{FEA8}", "\u{FEA6}"], // خ
        "\u{062F}" => ["\u{FEA9}", null, null, "\u{FEAA}"], // د
        "\u{0630}" => ["\u{FEAB}", null, null, "\u{FEAC}"], // ذ
        "\u{0631}" => ["\u{FEAD}", null, null, "\u{FEAE}"], // ر
        "\u{0632}" => ["\u{FEAF}", null, null, "\u{FEB0}"], // ز
        "\u{0633}" => ["\u{FEB1}", "\u{FEB3}", "\u{FEB4}", "\u{FEB2}"], // س
        "\u{0634}" => ["\u{FEB5}", "\u{FEB7}", "\u{FEB8}", "\u{FEB6}"], // ش
        "\u{0635}" => ["\u{FEB9}", "\u{FEBB}", "\u{FEBC}", "\u{FEBA}"], // ص
        "\u{0636}" => ["\u{FEBD}", "\u{FEBF}", "\u{FEC0}", "\u{FEBE}"], // ض
        "\u{0637}" => ["\u{FEC1}", "\u{FEC3}", "\u{FEC4}", "\u{FEC2}"], // ط
        "\u{0638}" => ["\u{FEC5}", "\u{FEC7}", "\u{FEC8}", "\u{FEC6}"], // ظ
        "\u{0639}" => ["\u{FEC9}", "\u{FECB}", "\u{FECC}", "\u{FECA}"], // ع
        "\u{063A}" => ["\u{FECD}", "\u{FECF}", "\u{FED0}", "\u{FECE}"], // غ
        "\u{0640}" => ["\u{0640}", "\u{0640}", "\u{0640}", "\u{0640}"], // ـ tatweel
        "\u{0641}" => ["\u{FED1}", "\u{FED3}", "\u{FED4}", "\u{FED2}"], // ف
        "\u{0642}" => ["\u{FED5}", "\u{FED7}", "\u{FED8}", "\u{FED6}"], // ق
        "\u{0643}" => ["\u{FED9}", "\u{FEDB}", "\u{FEDC}", "\u{FEDA}"], // ك
        "\u{0644}" => ["\u{FEDD}", "\u{FEDF}", "\u{FEE0}", "\u{FEDE}"], // ل
        "\u{0645}" => ["\u{FEE1}", "\u{FEE3}", "\u{FEE4}", "\u{FEE2}"], // م
        "\u{0646}" => ["\u{FEE5}", "\u{FEE7}", "\u{FEE8}", "\u{FEE6}"], // ن
        "\u{0647}" => ["\u{FEE9}", "\u{FEEB}", "\u{FEEC}", "\u{FEEA}"], // ه
        "\u{0648}" => ["\u{FEED}", null, null, "\u{FEEE}"], // و
        "\u{0649}" => ["\u{FEEF}", null, null, "\u{FEF0}"], // ى
        "\u{064A}" => ["\u{FEF1}", "\u{FEF3}", "\u{FEF4}", "\u{FEF2}"], // ي
        "\u{067E}" => ["\u{FB56}", "\u{FB58}", "\u{FB59}", "\u{FB57}"], // پ
        "\u{0686}" => ["\u{FB7A}", "\u{FB7C}", "\u{FB7D}", "\u{FB7B}"], // چ
        "\u{0698}" => ["\u{FB8A}", null, null, "\u{FB8B}"], // ژ
        "\u{06AF}" => ["\u{FB92}", "\u{FB94}", "\u{FB95}", "\u{FB93}"], // گ
        "\u{06A4}" => ["\u{FB6A}", "\u{FB6C}", "\u{FB6D}", "\u{FB6B}"], // ڤ
    ];

    private const LAM_ALEF = [
        "\u{0622}" => ["\u{FEF5}", "\u{FEF6}"], // لآ
        "\u{0623}" => ["\u{FEF7}", "\u{FEF8}"], // لأ
        "\u{0625}" => ["\u{FEF9}", "\u{FEFA}"], // لإ
        "\u{0627}" => ["\u{FEFB}", "\u{FEFC}"], // لا
    ];

    public static function shape(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        return implode("\n", array_map([self::class, 'shapeLine'], $lines));
    }

    private static function shapeLine(string $line): string
    {
        if (!preg_match('/[\x{0600}-\x{06FF}]/u', $line)) {
            return $line;
        }

        // تقسيم السطر إلى مقاطع إنجليزية/أرقام (مع المسافات بينها) ومقاطع عربية
        preg_match_all('/[A-Za-z0-9]+(?:[\s._\/:-]+[A-Za-z0-9]+)*|[\x{0600}-\x{06FF}\s\p{P}\p{S}]+|./u', $line, $matches);
        $tokens = $matches[0];
        $visual = [];

        foreach (array_reverse($tokens) as $tok) {
            if (preg_match('/^[A-Za-z0-9]/', $tok)) {
                $visual[] = $tok; // الإنجليزية والأرقام تبقى LTR
            } else {
                $visual[] = self::shapeArabicRun($tok);
            }
        }

        return implode('', $visual);
    }

    private static function shapeArabicRun(string $run): string
    {
        $chars = preg_split('//u', $run, -1, PREG_SPLIT_NO_EMPTY);
        $items = [];
        $n = count($chars);

        // دمج الحركات مع الحرف السابق + استبدال اللام ألف
        for ($i = 0; $i < $n; $i++) {
            $ch = $chars[$i];
            if (self::isTashkeel($ch) && $items) {
                $items[count($items) - 1]['marks'] .= $ch;
                continue;
            }
            if ($ch === "\u{0644}" && $i + 1 < $n) {
                $nextIdx = $i + 1;
                $marks = '';
                while ($nextIdx < $n && self::isTashkeel($chars[$nextIdx])) {
                    $marks .= $chars[$nextIdx];
                    $nextIdx++;
                }
                if ($nextIdx < $n && isset(self::LAM_ALEF[$chars[$nextIdx]])) {
                    $items[] = ['ch' => 'LA', 'alef' => $chars[$nextIdx], 'marks' => $marks];
                    $i = $nextIdx;
                    continue;
                }
            }
            $items[] = ['ch' => $ch, 'marks' => ''];
        }

        $count = count($items);
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $cur = $items[$i];
            $prevJoin = $i > 0 && self::joinsLeft($items[$i - 1]['ch']);
            $nextJoin = $i + 1 < $count && self::joinsRight($items[$i + 1]['ch']);

            if ($cur['ch'] === 'LA') {
                $glyph = self::LAM_ALEF[$cur['alef']][$prevJoin ? 1 : 0];
                $out[] = $glyph . $cur['marks'];
                continue;
            }

            if (!isset(self::GLYPHS[$cur['ch']])) {
                $out[] = self::mirrorBracket($cur['ch']) . $cur['marks'];
                continue;
            }

            $g = self::GLYPHS[$cur['ch']];
            $form = 0;
            if ($prevJoin && $nextJoin && $g[2] !== null) {
                $form = 2; // وسط
            } elseif ($prevJoin && $g[3] !== null) {
                $form = 3; // نهاية
            } elseif ($nextJoin && $g[1] !== null) {
                $form = 1; // بداية
            }
            $out[] = $g[$form] . $cur['marks'];
        }

        return implode('', array_reverse($out));
    }

    private static function joinsLeft(string $ch): bool
    {
        return isset(self::GLYPHS[$ch]) && self::GLYPHS[$ch][1] !== null;
    }

    private static function joinsRight(string $ch): bool
    {
        return $ch === 'LA' || isset(self::GLYPHS[$ch]);
    }

    private static function isTashkeel(string $ch): bool
    {
        $code = mb_ord($ch, 'UTF-8');
        return $code >= 0x064B && $code <= 0x065F;
    }

    private static function mirrorBracket(string $ch): string
    {
        return match ($ch) {
            '(' => ')', ')' => '(',
            '[' => ']', ']' => '[',
            '{' => '}', '}' => '{',
            '«' => '»', '»' => '«',
            default => $ch,
        };
    }
}
