<?php
/** ضغط عدة ملفات في أرشيف ZIP واحد باستخدام ZipArchive. */

function zip_tool(array $files, array $o): array
{
    if (!class_exists('ZipArchive')) {
        throw new ToolError('امتداد zip غير مفعّل في PHP. فعّل extension=zip في php.ini.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new ToolError('تعذّر إنشاء الأرشيف.');
    }
    $used = [];
    foreach ($files as $f) {
        $name = clean_filename($f['name']);
        // منع تكرار الأسماء داخل الأرشيف
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        for ($i = 2; isset($used[mb_strtolower($name)]); $i++) {
            $name = "$base ($i)" . ($ext !== '' ? ".$ext" : '');
        }
        $used[mb_strtolower($name)] = true;
        $zip->addFile($f['tmp_name'], $name);
    }
    $zip->close();
    $data = file_get_contents($tmp);
    unlink($tmp);

    $archive = clean_filename($o['archive'] ?: 'files');
    return ['data' => $data, 'name' => preg_replace('/\.zip$/i', '', $archive) . '.zip', 'mime' => 'application/zip',
            'info' => count($files) . ' ملف'];
}

