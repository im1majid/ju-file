<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExternalHostService
{
    /**
     * رفع الملف إلى Cloudinary أولاً (تخزين دائم) ثم استضافات مجانية مؤقتة كاحتياط.
     */
    public function upload(string $filePath, string $originalName): ?string
    {
        $cleanName = ToolCatalog::cleanFilename($originalName);
        $contents  = @file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        // 1) Cloudinary (التخزين الدائم المعتمد عند ضبط المفاتيح)
        if ($cloudinaryUrl = $this->uploadToCloudinary($contents, $cleanName)) {
            return $cloudinaryUrl;
        }

        // 2) x0.at
        $res = $this->postFile('https://x0.at', 'file', $contents, $cleanName, ['User-Agent' => 'curl/8.5.0']);
        if ($res && $res->successful()) {
            $body = trim($res->body());
            if (preg_match('#^https://x0\.at/\S+$#', $body)) {
                return $body;
            }
        }

        // 3) uguu.se
        $res = $this->postFile('https://uguu.se/upload', 'files[]', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('files.0.url');
            if (is_string($url) && str_starts_with($url, 'https://')) {
                return $url;
            }
        }

        // 4) tmpfiles.org
        $res = $this->postFile('https://tmpfiles.org/api/v1/upload', 'file', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('data.url');
            if (is_string($url)) {
                return preg_replace('#^https?://tmpfiles\.org/(\d+)/#', 'https://tmpfiles.org/dl/$1/', $url);
            }
        }

        return null;
    }

    private function uploadToCloudinary(string $contents, string $filename): ?string
    {
        $cloud  = trim((string) config('services.cloudinary.cloud_name'));
        $key    = trim((string) config('services.cloudinary.api_key'));
        $secret = trim((string) config('services.cloudinary.api_secret'));
        $folder = trim((string) config('services.cloudinary.folder', 'ju-file')) ?: 'ju-file';

        if ($cloud === '' || $key === '' || $secret === '') {
            return null;
        }

        $timestamp = time();
        $signature = sha1("folder={$folder}&timestamp={$timestamp}{$secret}");
        $endpoint  = "https://api.cloudinary.com/v1_1/{$cloud}/auto/upload";

        $res = $this->postFile($endpoint, 'file', $contents, $filename, [], [
            'api_key'   => $key,
            'folder'    => $folder,
            'timestamp' => (string) $timestamp,
            'signature' => $signature,
        ], 60);

        if ($res && $res->successful()) {
            $url = $res->json('secure_url');
            if (is_string($url) && str_starts_with($url, 'https://')) {
                return $url;
            }
        }

        return null;
    }

    private function postFile(
        string $url,
        string $field,
        string $contents,
        string $filename,
        array $headers = [],
        array $extraFields = [],
        int $timeout = 25
    ): ?Response {
        foreach ([true, false] as $verify) {
            try {
                $client = Http::timeout($timeout)->withHeaders($headers);
                if (!$verify) {
                    $client = $client->withoutVerifying();
                }
                return $client->attach($field, $contents, $filename)->post($url, $extraFields);
            } catch (\Throwable $e) {
                if ($verify && str_contains($e->getMessage(), 'cURL error 60')) {
                    continue;
                }
                return null;
            }
        }
        return null;
    }
}
