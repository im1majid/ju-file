<?php

namespace App\Services;

use App\Models\Share;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExternalHostService
{
    /**
     * رفع الملف إلى Cloudinary أولاً (ويُرجع بياناته لحذف النسخة المحلية وحذفه لاحقاً عند الانتهاء)،
     * ثم الاستضافات المؤقتة كاحتياط.
     *
     * @return array{url: string, public_id: ?string, resource_type: ?string, is_cloudinary: bool}|null
     */
    public function upload(string $filePath, string $originalName): ?array
    {
        $cleanName = ToolCatalog::cleanFilename($originalName);
        $contents  = @file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        // 1) Cloudinary
        if ($cl = $this->uploadToCloudinary($contents, $cleanName)) {
            return $cl;
        }

        // 2) x0.at
        $res = $this->postFile('https://x0.at', 'file', $contents, $cleanName, ['User-Agent' => 'curl/8.5.0']);
        if ($res && $res->successful()) {
            $body = trim($res->body());
            if (preg_match('#^https://x0\.at/\S+$#', $body)) {
                return ['url' => $body, 'public_id' => null, 'resource_type' => null, 'is_cloudinary' => false];
            }
        }

        // 3) uguu.se
        $res = $this->postFile('https://uguu.se/upload', 'files[]', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('files.0.url');
            if (is_string($url) && str_starts_with($url, 'https://')) {
                return ['url' => $url, 'public_id' => null, 'resource_type' => null, 'is_cloudinary' => false];
            }
        }

        // 4) tmpfiles.org
        $res = $this->postFile('https://tmpfiles.org/api/v1/upload', 'file', $contents, $cleanName);
        if ($res && $res->successful()) {
            $url = $res->json('data.url');
            if (is_string($url)) {
                return [
                    'url'           => preg_replace('#^https?://tmpfiles\.org/(\d+)/#', 'https://tmpfiles.org/dl/$1/', $url),
                    'public_id'     => null,
                    'resource_type' => null,
                    'is_cloudinary' => false,
                ];
            }
        }

        return null;
    }

    /**
     * حذف الملف من القرص المحلي ومن Cloudinary عند حذف المشاركة أو انتهاء صلاحيتها.
     */
    public function purgeShareAssets(Share $share): void
    {
        @unlink(storage_path('app/uploads/' . $share->stored_name));

        if ($share->cloudinary_public_id) {
            $this->destroyCloudinary($share->cloudinary_public_id, $share->cloudinary_resource_type ?: 'image');
        }
    }

    private function uploadToCloudinary(string $contents, string $filename): ?array
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
        ], 90);

        if ($res && $res->successful()) {
            $url = $res->json('secure_url');
            if (is_string($url) && str_starts_with($url, 'https://')) {
                return [
                    'url'           => $url,
                    'public_id'     => $res->json('public_id'),
                    'resource_type' => $res->json('resource_type') ?: 'image',
                    'is_cloudinary' => true,
                ];
            }
        }

        return null;
    }

    private function destroyCloudinary(string $publicId, string $resourceType): void
    {
        $cloud  = trim((string) config('services.cloudinary.cloud_name'));
        $key    = trim((string) config('services.cloudinary.api_key'));
        $secret = trim((string) config('services.cloudinary.api_secret'));

        if ($cloud === '' || $key === '' || $secret === '') {
            return;
        }

        $type      = in_array($resourceType, ['image', 'video', 'raw'], true) ? $resourceType : 'image';
        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$secret}");
        $endpoint  = "https://api.cloudinary.com/v1_1/{$cloud}/{$type}/destroy";

        foreach ([true, false] as $verify) {
            try {
                $client = Http::timeout(15)->asForm();
                if (!$verify) {
                    $client = $client->withoutVerifying();
                }
                $client->post($endpoint, [
                    'public_id' => $publicId,
                    'api_key'   => $key,
                    'timestamp' => (string) $timestamp,
                    'signature' => $signature,
                ]);
                return;
            } catch (\Throwable $e) {
                if ($verify && str_contains($e->getMessage(), 'cURL error 60')) {
                    continue;
                }
                return;
            }
        }
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
