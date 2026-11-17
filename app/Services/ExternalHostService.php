<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExternalHostService
{
    /**
     * رفع الملف إلى خدمة استضافة عامة لإرجاع رابط خارجي حقيقي (وليس localhost).
     * يحاول x0.at ثم uguu.se ثم tmpfiles.org بالترتيب.
     */
    public function upload(string $filePath, string $originalName): ?string
    {
        $cleanName = ToolCatalog::cleanFilename($originalName);
        $contents  = @file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        // 1) x0.at
        $res = $this->postFile('https://x0.at', 'file', $contents, $cleanName, ['User-Agent' => 'curl/8.5.0']);
        if ($res && $res->successful()) {
            $body = trim($res->body());
            if (preg_match('#^https://x0\.at/\S+$#', $body)) {
                return $body;
            }
        }

        // 2) uguu.se
        $res = $this->postFile('https://uguu.se/upload', 'files[]', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('files.0.url');
            if (is_string($url) && str_starts_with($url, 'https://')) {
                return $url;
            }
        }

        // 3) tmpfiles.org
        $res = $this->postFile('https://tmpfiles.org/api/v1/upload', 'file', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('data.url');
            if (is_string($url)) {
                return preg_replace('#^https?://tmpfiles\.org/(\d+)/#', 'https://tmpfiles.org/dl/$1/', $url);
            }
        }

        return null;
    }

    private function postFile(string $url, string $field, string $contents, string $filename, array $headers = []): ?Response
    {
        foreach ([true, false] as $verify) {
            try {
                $client = Http::timeout(25)->withHeaders($headers);
                if (!$verify) {
                    $client = $client->withoutVerifying();
                }
                return $client->attach($field, $contents, $filename)->post($url);
            } catch (\Throwable $e) {
                // في بيئة ويندوز المحلية قد لا يكون ملف شهادات CA مفعّلاً في php.ini
                if ($verify && str_contains($e->getMessage(), 'cURL error 60')) {
                    continue;
                }
                return null;
            }
        }
        return null;
    }
}
