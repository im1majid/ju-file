<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocialDownloadController extends Controller
{
    private const HOSTS = [
        'ytstream' => 'ytstream-download-youtube-videos.p.rapidapi.com',
        'yt_api'   => 'yt-api.p.rapidapi.com',
        'smvd'     => 'social-media-video-downloader.p.rapidapi.com',
        'all_in_1' => 'instagram-downloader-download-instagram-videos-stories1.p.rapidapi.com',
        'full_dl'  => 'full-downloader-social-media.p.rapidapi.com',
    ];

    public function index(): View
    {
        return view('social_download', [
            'videoUrl'       => '',
            'downloadMode'   => 'media',
            'errorMsg'       => '',
            'mediaTitle'     => '',
            'mediaThumb'     => '',
            'availableLinks' => [],
        ]);
    }

    public function fetch(Request $request): View
    {
        @set_time_limit(60);

        $videoUrl       = trim((string) $request->input('video_url', ''));
        $downloadMode   = $request->input('download_mode') === 'audio' ? 'audio' : 'media';
        $errorMsg       = '';
        $mediaTitle     = '';
        $mediaThumb     = '';
        $availableLinks = [];

        if ($videoUrl === '') {
            $errorMsg = __('يرجى لصق رابط المقطع أولاً.');
        } elseif (!$this->isSafeExternalUrl($videoUrl)) {
            $errorMsg = __('الرابط المدخل غير صالح. يرجى لصق رابط كامل يبدأ بـ https://');
        } else {
            $ytId = $this->extractYoutubeId($videoUrl);

            // 1. مسار يوتيوب: جلب جميع الجودات الأصلية المتوفرة فعلياً
            if ($ytId) {
                $ytData = $this->fetchYoutubeLocalStreams($ytId);
                if ($ytData) {
                    $mediaTitle     = $ytData['title'];
                    $mediaThumb     = $ytData['thumb'];
                    $availableLinks = $this->buildYoutubeQualityLinks($ytData, $downloadMode);
                }

                if (empty($availableLinks)) {
                    $errorMsg = __('تعذر استخراج جودات هذا الفيديو من يوتيوب. تأكد من أن الفيديو عام.');
                }
            } else {
                // 2. مسار باقي المنصات (Instagram, TikTok, X, Facebook)
                $rapidApiKey = (string) config('services.rapidapi.key', '');

                if ($rapidApiKey === '') {
                    $errorMsg = __('مفتاح RapidAPI غير مُعد في إعدادات الخادم (RAPIDAPI_KEY). تحميل يوتيوب يعمل مباشرة، ولباقي المنصات أضف المفتاح في ملف .env.');
                } else {
                    $encUrl   = urlencode($videoUrl);
                    $attempts = [
                        ['host' => self::HOSTS['full_dl'],  'url' => 'https://' . self::HOSTS['full_dl'] . '/url?url=' . $encUrl],
                        ['host' => self::HOSTS['all_in_1'], 'url' => 'https://' . self::HOSTS['all_in_1'] . '/dl?url=' . $encUrl],
                        ['host' => self::HOSTS['all_in_1'], 'url' => 'https://' . self::HOSTS['all_in_1'] . '/get-info-rapidapi?url=' . $encUrl],
                        ['host' => self::HOSTS['smvd'],     'url' => 'https://' . self::HOSTS['smvd'] . '/smvd/get/all?url=' . $encUrl],
                    ];

                    foreach ($attempts as $att) {
                        $curl = curl_init();
                        curl_setopt_array($curl, [
                            CURLOPT_URL            => $att['url'],
                            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_ENCODING       => '',
                            CURLOPT_TIMEOUT        => 20,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_HTTPHEADER     => [
                                'x-rapidapi-host: ' . $att['host'],
                                'x-rapidapi-key: ' . $rapidApiKey,
                            ],
                        ]);
                        $response = curl_exec($curl);
                        $err      = curl_error($curl);
                        curl_close($curl);

                        if ($err || empty($response)) {
                            continue;
                        }
                        $json = json_decode($response, true);
                        if (!is_array($json)) {
                            continue;
                        }

                        $parsed = $this->parseSocialResponse($json, $downloadMode);
                        if (!empty($parsed['links'])) {
                            $mediaTitle     = $parsed['title'];
                            $mediaThumb     = $parsed['thumb'];
                            $availableLinks = $parsed['links'];
                            break;
                        }
                    }

                    if (empty($availableLinks)) {
                        $errorMsg = __('تعذر جلب المقطع. تأكد من أن الرابط عام وصحيح وليس من حساب خاص.');
                    }
                }
            }
        }

        // توليد روابط بث موقّعة مؤقتاً لكل جودة لمنع استغلال نقطة البث كبروكسي مفتوح
        foreach ($availableLinks as &$item) {
            $ext = $item['mode'] === 'audio' ? ($item['format'] ?: 'm4a') : ($item['format'] ?: 'mp4');
            $item['proxy_video'] = URL::temporarySignedRoute(
                'social.download.stream',
                now()->addHours(2),
                ['direct_dl' => $item['url'], 'ext' => $ext],
                absolute: false
            );
            $item['proxy_audio'] = !empty($item['audio_url'])
                ? URL::temporarySignedRoute(
                    'social.download.stream',
                    now()->addHours(2),
                    ['direct_dl' => $item['audio_url'], 'ext' => 'm4a'],
                    absolute: false
                )
                : '';
        }
        unset($item);

        return view('social_download', compact(
            'videoUrl',
            'downloadMode',
            'errorMsg',
            'mediaTitle',
            'mediaThumb',
            'availableLinks'
        ));
    }

    /**
     * معالج البث والتحميل المباشر في اتصال واحد مكتمل المدى
     */
    public function stream(Request $request): StreamedResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403, 'Invalid or expired stream link');

        $fileUrl = (string) $request->query('direct_dl', '');
        $ext     = preg_replace('/[^a-z0-9]/i', '', (string) $request->query('ext', 'mp4')) ?: 'mp4';

        abort_unless($this->isSafeExternalUrl($fileUrl), 400, 'Invalid URL');

        $filename    = 'jufile_' . date('Ymd_His') . '.' . strtolower($ext);
        $ua          = $this->getStreamUserAgent($fileUrl);
        $totalLength = 0;
        $rangeHeader = null;

        // تحقيق شرط rqh=1 في يوتيوب عبر إرسال المدى الكامل للملف (من 0 إلى clen-1) في طلب واحد متصل
        if (str_contains($fileUrl, 'googlevideo.com')) {
            if (preg_match('/[?&]clen=(\d+)/i', $fileUrl, $m) && (int) $m[1] > 0) {
                $totalLength = (int) $m[1];
                $rangeHeader = '0-' . ($totalLength - 1);
            } else {
                $rangeHeader = '0-';
            }
        }

        $headers = [
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => 'application/octet-stream',
            'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control'             => 'no-cache, must-revalidate',
            'Pragma'                    => 'public',
            'X-Accel-Buffering'         => 'no',
        ];

        if ($totalLength > 0) {
            $headers['Content-Length'] = (string) $totalLength;
        }

        return response()->stream(function () use ($fileUrl, $ua, $rangeHeader, $totalLength) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');

            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $isGoogleVideo = str_contains($fileUrl, 'googlevideo.com');

            // سحب ملفات يوتيوب على دفعات (4MB لكل دفعة) عبر نفس اتصال Keep-Alive لتفادي تقييد السرعة (throttling) أو رفض المدى الطويل
            if ($isGoogleVideo && $totalLength > 0) {
                $chunkSize = 4 * 1024 * 1024;
                $offset    = 0;
                $ch        = curl_init();

                while ($offset < $totalLength) {
                    $end        = min($offset + $chunkSize - 1, $totalLength - 1);
                    $statusCode = 200;
                    $chunkBytes = 0;

                    curl_setopt_array($ch, [
                        CURLOPT_URL            => $fileUrl,
                        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS      => 5,
                        CURLOPT_RETURNTRANSFER => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_TIMEOUT        => 60,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_BUFFERSIZE     => 262144,
                        CURLOPT_USERAGENT      => $ua,
                        CURLOPT_HTTPHEADER     => ['Accept: */*'],
                        CURLOPT_RANGE          => $offset . '-' . $end,
                        CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$statusCode) {
                            if (preg_match('/^HTTP\/\d(?:\.\d)?\s+(\d+)/i', $headerLine, $m)) {
                                $statusCode = (int) $m[1];
                            }
                            return strlen($headerLine);
                        },
                        CURLOPT_WRITEFUNCTION  => function ($curl, $chunk) use (&$statusCode, &$chunkBytes) {
                            if ($statusCode >= 400) {
                                return 0;
                            }
                            $len = strlen($chunk);
                            $chunkBytes += $len;
                            echo $chunk;
                            flush();
                            return $len;
                        },
                    ]);

                    curl_exec($ch);
                    $curlErr = curl_error($ch);

                    if ($statusCode >= 400 || $chunkBytes === 0) {
                        Log::warning('[SocialDownload Stream Chunk Error]', [
                            'http'   => $statusCode,
                            'offset' => $offset,
                            'end'    => $end,
                            'err'    => $curlErr,
                        ]);
                        break;
                    }

                    $offset += $chunkBytes;
                }

                curl_close($ch);
                return;
            }

            $statusCode = 200;
            $curlOpts   = [
                CURLOPT_URL            => $fileUrl,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_BUFFERSIZE     => 262144, // 256KB لسرعة تدفق عالية
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_HTTPHEADER     => ['Accept: */*'],
                CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$statusCode) {
                    if (preg_match('/^HTTP\/\d(?:\.\d)?\s+(\d+)/i', $headerLine, $m)) {
                        $statusCode = (int) $m[1];
                    }
                    return strlen($headerLine);
                },
                CURLOPT_WRITEFUNCTION  => function ($curl, $chunk) use (&$statusCode) {
                    if ($statusCode >= 400) {
                        return 0;
                    }
                    echo $chunk;
                    flush();
                    return strlen($chunk);
                },
            ];

            if ($rangeHeader !== null) {
                $curlOpts[CURLOPT_RANGE] = $rangeHeader;
            }

            $ch = curl_init();
            curl_setopt_array($ch, $curlOpts);
            curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($statusCode >= 400 || $curlErr !== '') {
                Log::warning('[SocialDownload Stream Error]', [
                    'http'  => $statusCode,
                    'range' => $rangeHeader,
                    'err'   => $curlErr,
                ]);
            }
        }, 200, $headers);
    }

    private function getStreamUserAgent(string $url): string
    {
        if (str_contains($url, 'c=VISIONOS')) {
            return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_7_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15';
        }
        if (str_contains($url, 'c=IOS')) {
            return 'com.google.ios.youtube/20.03.02 (iPhone16,2; U; CPU iOS 18_2_1 like Mac OS X;)';
        }
        if (str_contains($url, 'c=ANDROID_VR')) {
            return 'com.google.android.apps.youtube.vr.oculus/1.60.19 (Linux; U; Android 12L; eureka-user Build/SQ3A.220605.009.A1) gzip';
        }
        if (str_contains($url, 'c=ANDROID')) {
            return 'com.google.android.youtube/20.10.38 (Linux; U; Android 14) gzip';
        }

        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
    }

    private function isSafeExternalUrl(string $url): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts  = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host   = strtolower($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.local')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    private function extractYoutubeId(string $url): string|false
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        return preg_match($pattern, $url, $m) ? $m[1] : false;
    }

    private function fetchYoutubeVisitorData(): string
    {
        $ch = curl_init('https://www.youtube.com/youtubei/v1/visitor_id?prettyPrint=false');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_POSTFIELDS     => json_encode([
                'context' => [
                    'client' => [
                        'clientName'    => 'WEB',
                        'clientVersion' => '2.20250925.01.00',
                        'hl'            => 'en',
                        'gl'            => 'US',
                    ],
                ],
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            return '';
        }
        $data = json_decode($res, true);
        return is_string($data['responseContext']['visitorData'] ?? null)
            ? $data['responseContext']['visitorData']
            : '';
    }

    private function fetchYoutubeLocalStreams(string $videoId): ?array
    {
        $visitorData = $this->fetchYoutubeVisitorData();
        $visionUa    = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_7_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15';

        $clients = [
            [
                'name'          => 'VISIONOS',
                'clientId'      => '101',
                'ua'            => $visionUa,
                'adaptiveSafe'  => true,
                'client'        => array_filter([
                    'clientName'       => 'VISIONOS',
                    'clientVersion'    => '1.02',
                    'deviceMake'       => 'Apple',
                    'deviceModel'      => 'RealityDevice17,1',
                    'osName'           => 'visionOS',
                    'osVersion'        => '26.5.23O471',
                    'hl'               => 'en',
                    'gl'               => 'US',
                    'timeZone'         => 'UTC',
                    'utcOffsetMinutes' => 0,
                    'visitorData'      => $visitorData ?: null,
                ], fn ($v) => $v !== null),
            ],
            [
                'name'          => 'ANDROID',
                'clientId'      => '3',
                'ua'            => 'com.google.android.youtube/20.10.38 (Linux; U; Android 14) gzip',
                'adaptiveSafe'  => false, // مسار ANDROID التكيفي يتطلب PO Token، بينما formats (itag=18) يعمل مباشرة
                'client'        => [
                    'clientName'        => 'ANDROID',
                    'clientVersion'     => '20.10.38',
                    'androidSdkVersion' => 34,
                    'osName'            => 'Android',
                    'osVersion'         => '14',
                    'hl'                => 'en',
                    'gl'                => 'US',
                    'timeZone'          => 'UTC',
                    'utcOffsetMinutes'  => 0,
                ],
            ],
            [
                'name'          => 'ANDROID_VR',
                'clientId'      => '28',
                'ua'            => 'com.google.android.apps.youtube.vr.oculus/1.60.19 (Linux; U; Android 12L; eureka-user Build/SQ3A.220605.009.A1) gzip',
                'adaptiveSafe'  => true,
                'client'        => array_filter([
                    'clientName'        => 'ANDROID_VR',
                    'clientVersion'     => '1.60.19',
                    'deviceMake'        => 'Oculus',
                    'deviceModel'       => 'Quest 3',
                    'androidSdkVersion' => 32,
                    'userAgent'         => 'com.google.android.apps.youtube.vr.oculus/1.60.19 (Linux; U; Android 12L; eureka-user Build/SQ3A.220605.009.A1) gzip',
                    'osName'            => 'Android',
                    'osVersion'         => '12L',
                    'hl'                => 'en',
                    'gl'                => 'US',
                    'timeZone'          => 'UTC',
                    'utcOffsetMinutes'  => 0,
                    'visitorData'       => $visitorData ?: null,
                ], fn ($v) => $v !== null),
            ],
        ];

        foreach ($clients as $c) {
            $payload = json_encode([
                'videoId'        => $videoId,
                'context'        => ['client' => $c['client']],
                'contentCheckOk' => true,
                'racyCheckOk'    => true,
            ]);

            $headers = [
                'Content-Type: application/json',
                'X-YouTube-Client-Name: ' . $c['clientId'],
                'X-YouTube-Client-Version: ' . $c['client']['clientVersion'],
            ];
            if ($visitorData !== '') {
                $headers[] = 'X-Goog-Visitor-Id: ' . $visitorData;
            }

            $ch = curl_init('https://www.youtube.com/youtubei/v1/player?prettyPrint=false');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => $c['ua'],
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            if (!$res) {
                continue;
            }
            $data = json_decode($res, true);
            if (!is_array($data)) {
                continue;
            }

            $playStatus = $data['playabilityStatus']['status'] ?? 'UNKNOWN';

            if ($playStatus === 'OK' && !empty($data['streamingData'])) {
                $thumbs   = $data['videoDetails']['thumbnail']['thumbnails'] ?? [];
                $thumbUrl = !empty($thumbs) ? end($thumbs)['url'] : "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";

                return [
                    'title'           => $data['videoDetails']['title'] ?? __('فيديو يوتيوب'),
                    'thumb'           => $thumbUrl,
                    'formats'         => $data['streamingData']['formats'] ?? [],
                    'adaptiveFormats' => $c['adaptiveSafe'] ? ($data['streamingData']['adaptiveFormats'] ?? []) : [],
                ];
            }
        }

        return null;
    }

    private function buildYoutubeQualityLinks(array $ytData, string $downloadMode): array
    {
        $links    = [];
        $formats  = is_array($ytData['formats'] ?? null) ? $ytData['formats'] : [];
        $adaptive = is_array($ytData['adaptiveFormats'] ?? null) ? $ytData['adaptiveFormats'] : [];

        // 1. اختيار المسار الصوتي القياسي المستقر (itag=140) وتجنب (itag=139)
        $bestAudioUrl  = '';
        $bestAudioSize = 0;

        foreach ($adaptive as $af) {
            if ((int) ($af['itag'] ?? 0) === 140 && !empty($af['url'])) {
                $bestAudioUrl  = $af['url'];
                $bestAudioSize = isset($af['contentLength']) ? (int) $af['contentLength'] : 0;
                break;
            }
        }

        if ($bestAudioUrl === '') {
            foreach ($adaptive as $af) {
                $itag = (int) ($af['itag'] ?? 0);
                $mime = strtolower($af['mimeType'] ?? '');
                if ($itag !== 139 && str_contains($mime, 'audio/mp4') && !empty($af['url'])) {
                    $bestAudioUrl  = $af['url'];
                    $bestAudioSize = isset($af['contentLength']) ? (int) $af['contentLength'] : 0;
                    break;
                }
            }
        }

        $mergedFallback = $formats[0]['url'] ?? '';
        $audioSource    = $bestAudioUrl ?: $mergedFallback;

        if ($downloadMode === 'audio') {
            if ($audioSource !== '') {
                $audioFormats = [
                    'mp3' => 'MP3 (320kbps)',
                    'm4a' => __('M4A / MP4 (أصلي)'),
                    'wav' => __('WAV (خام)'),
                ];
                foreach ($audioFormats as $fmtKey => $fmtLabel) {
                    $links[] = [
                        'mode'       => 'audio',
                        'url'        => $audioSource,
                        'audio_url'  => '',
                        'video_size' => $bestAudioSize,
                        'audio_size' => 0,
                        'format'     => $fmtKey,
                        'label'      => $fmtLabel,
                    ];
                }
            }
            return $links;
        }

        $qualityMap = [];

        foreach ($formats as $vf) {
            if (empty($vf['url'])) {
                continue;
            }
            $mime = strtolower($vf['mimeType'] ?? '');
            if (!str_contains($mime, 'video/mp4')) {
                continue;
            }

            $h      = isset($vf['height']) ? (int) $vf['height'] : 0;
            $qLabel = strtolower(trim($vf['qualityLabel'] ?? ''));
            if ($h <= 0 && preg_match('/(\d+)p/i', $qLabel, $m)) {
                $h = (int) $m[1];
            }
            if ($h <= 0) {
                $h = 360;
            }

            $qualityMap[$h] = [
                'mode'       => 'direct',
                'url'        => $vf['url'],
                'audio_url'  => '',
                'video_size' => isset($vf['contentLength']) ? (int) $vf['contentLength'] : 0,
                'audio_size' => 0,
                'format'     => 'mp4',
                'label'      => $h . 'P',
            ];
        }

        if ($bestAudioUrl !== '') {
            foreach ($adaptive as $vf) {
                if (empty($vf['url'])) {
                    continue;
                }
                $mime = strtolower($vf['mimeType'] ?? '');
                if (!str_contains($mime, 'video/mp4')) {
                    continue;
                }

                $h      = isset($vf['height']) ? (int) $vf['height'] : 0;
                $qLabel = strtolower(trim($vf['qualityLabel'] ?? ''));
                if ($h <= 0 && preg_match('/(\d+)p/i', $qLabel, $m)) {
                    $h = (int) $m[1];
                }
                if ($h <= 0 || isset($qualityMap[$h])) {
                    continue;
                }

                $vSize   = isset($vf['contentLength']) ? (int) $vf['contentLength'] : 0;
                $totalMb = ($vSize > 0) ? round(($vSize + $bestAudioSize) / (1024 * 1024), 1) . ' MB' : '';

                $qualityMap[$h] = [
                    'mode'       => 'merge',
                    'url'        => $vf['url'],
                    'audio_url'  => $bestAudioUrl,
                    'video_size' => $vSize,
                    'audio_size' => $bestAudioSize,
                    'format'     => 'mp4',
                    'label'      => $h . 'P' . ($h >= 720 ? ' HD' : '') . ($totalMb ? ' - ' . $totalMb : ''),
                ];
            }
        }

        krsort($qualityMap, SORT_NUMERIC);
        return array_values($qualityMap);
    }

    private function detectMediaExtension(string $url, string $fallback = 'mp4'): string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        if (preg_match('/\.(jpg|jpeg|png|webp|gif|mp4|webm|m4a|mp3)$/i', $path, $m)) {
            return strtolower($m[1]);
        }
        return $fallback;
    }

    private function parseSocialResponse(array $data, string $downloadMode): array
    {
        $res = ['title' => '', 'thumb' => '', 'links' => []];

        if (isset($data['message']) && preg_match('/not subscribed|does not exist|Too many requests|exceeded|quota|disabled/i', (string) $data['message'])) {
            return $res;
        }

        $rawTitle = $data['title'] ?? $data['caption'] ?? ($data['data']['title'] ?? $data['data']['caption'] ?? ($data['result']['title'] ?? ''));
        if (is_string($rawTitle) && trim($rawTitle) !== '') {
            $clean = trim(str_replace(['\n', "\n", "\r"], ' ', $rawTitle));
            $res['title'] = (mb_strlen($clean) > 90) ? mb_substr($clean, 0, 90) . '...' : $clean;
        } else {
            $res['title'] = __('الملف جاهز للتحميل');
        }

        $tc = $data['thumbnail'] ?? $data['picture'] ?? $data['thumb'] ?? ($data['data']['thumbnail'] ?? ($data['result']['thumbnail'] ?? ''));
        if (is_string($tc)) {
            $res['thumb'] = $tc;
        }

        $audioFormats = [
            'mp3' => 'MP3 (320kbps)',
            'm4a' => __('M4A / MP4 (أصلي)'),
            'wav' => __('WAV (خام)'),
        ];

        $directCandidates = [
            $data['file'] ?? '',
            $data['dlink'] ?? '',
            $data['download_url'] ?? '',
            $data['url'] ?? '',
            $data['video_url'] ?? '',
            $data['data']['download_url'] ?? '',
            $data['data']['url'] ?? '',
            $data['result']['download_url'] ?? '',
            $data['result']['url'] ?? '',
        ];

        foreach ($directCandidates as $cand) {
            if (is_string($cand) && filter_var($cand, FILTER_VALIDATE_URL)) {
                if ($downloadMode === 'audio') {
                    foreach ($audioFormats as $fmtKey => $fmtLabel) {
                        $res['links'][] = [
                            'mode'       => 'audio',
                            'url'        => $cand,
                            'audio_url'  => '',
                            'video_size' => 0,
                            'audio_size' => 0,
                            'format'     => $fmtKey,
                            'label'      => $fmtLabel,
                        ];
                    }
                } else {
                    $ext = $this->detectMediaExtension($cand, 'mp4');
                    $res['links'][] = [
                        'mode'       => 'direct',
                        'url'        => $cand,
                        'audio_url'  => '',
                        'video_size' => 0,
                        'audio_size' => 0,
                        'format'     => $ext,
                        'label'      => __('الجودة الأصلية الكاملة (:ext)', ['ext' => strtoupper($ext)]),
                    ];
                }
                return $res;
            }
        }

        $lists = [
            $data['links'] ?? null,
            $data['medias'] ?? null,
            $data['data']['links'] ?? null,
            $data['data']['medias'] ?? null,
            $data['result']['medias'] ?? null,
            $data['result']['links'] ?? null,
        ];

        $activeList = [];
        foreach ($lists as $l) {
            if (is_array($l) && count($l) > 0) {
                $activeList = $l;
                break;
            }
        }

        if (!empty($activeList)) {
            $seenLabels = [];
            $firstUrl   = '';

            foreach ($activeList as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $u = $item['link'] ?? $item['download_url'] ?? $item['url'] ?? $item['video_url'] ?? '';
                if (!is_string($u) || !filter_var($u, FILTER_VALIDATE_URL)) {
                    continue;
                }

                if ($firstUrl === '') {
                    $firstUrl = $u;
                }

                $qRaw  = strtolower((string) ($item['quality'] ?? $item['qualityLabel'] ?? $item['format'] ?? 'mp4'));
                $isAud = str_contains($qRaw, 'audio') || str_contains($qRaw, 'mp3') || str_contains($qRaw, 'm4a');

                if ($downloadMode !== 'audio' && !$isAud) {
                    $ext = $this->detectMediaExtension($u, strtolower((string) ($item['extension'] ?? $item['ext'] ?? 'mp4')));
                    $lbl = strtoupper((string) ($item['quality'] ?? $item['qualityLabel'] ?? $ext));
                    if (!isset($seenLabels[$lbl])) {
                        $seenLabels[$lbl] = true;
                        $res['links'][] = [
                            'mode'       => 'direct',
                            'url'        => $u,
                            'audio_url'  => '',
                            'video_size' => 0,
                            'audio_size' => 0,
                            'format'     => $ext,
                            'label'      => $lbl,
                        ];
                    }
                }
            }

            if ($downloadMode === 'audio' && $firstUrl !== '') {
                foreach ($audioFormats as $fmtKey => $fmtLabel) {
                    $res['links'][] = [
                        'mode'       => 'audio',
                        'url'        => $firstUrl,
                        'audio_url'  => '',
                        'video_size' => 0,
                        'audio_size' => 0,
                        'format'     => $fmtKey,
                        'label'      => $fmtLabel,
                    ];
                }
            }
        }

        return $res;
    }
}
