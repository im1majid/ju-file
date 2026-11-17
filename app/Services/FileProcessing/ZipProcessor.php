<?php

namespace App\Services\FileProcessing;

use App\Services\ToolCatalog;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ZipProcessor
{
    /** @param UploadedFile[] $files */
    public function process(array $files, array $o): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new ToolException('امتداد zip غير مفعّل في PHP. فعّل extension=zip في php.ini.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new ToolException('تعذّر إنشاء الأرشيف.');
        }

        $used = [];
        foreach ($files as $f) {
            $name = ToolCatalog::cleanFilename($f->getClientOriginalName());
            $base = pathinfo($name, PATHINFO_FILENAME);
            $ext  = pathinfo($name, PATHINFO_EXTENSION);
            for ($i = 2; isset($used[mb_strtolower($name)]); $i++) {
                $name = "{$base} ({$i})" . ($ext !== '' ? ".{$ext}" : '');
            }
            $used[mb_strtolower($name)] = true;
            $zip->addFile($f->getRealPath(), $name);
        }
        $zip->close();

        $data = file_get_contents($tmp);
        @unlink($tmp);

        $archive = ToolCatalog::cleanFilename($o['archive'] ?: 'files');
        return [
            'data' => $data,
            'name' => preg_replace('/\.zip$/i', '', $archive) . '.zip',
            'mime' => 'application/zip',
            'info' => count($files) . ' ملف',
        ];
    }
}

