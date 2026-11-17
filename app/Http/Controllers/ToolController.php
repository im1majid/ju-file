<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Services\FileProcessing\ImageProcessor;
use App\Services\FileProcessing\TextProcessor;
use App\Services\FileProcessing\ToolException;
use App\Services\FileProcessing\ZipProcessor;
use App\Services\ToolCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function category(string $category): View
    {
        $cats = ToolCatalog::categories();
        abort_unless(isset($cats[$category]), 404);

        $cat = $cats[$category];
        $tools = $cat['tools'];

        $engines = array_values(array_unique(array_column($tools, 'engine')));
        $scripts = [];
        if (in_array('ffmpeg', $engines, true)) {
            $scripts[] = 'assets/vendor/ffmpeg.js';
            $scripts[] = 'assets/js/media.js?v=4';
        }
        if (in_array('pdf', $engines, true)) {
            $scripts[] = 'assets/vendor/pdf-lib.min.js';
            $scripts[] = 'assets/js/pdf.js?v=3';
        }

        return view('tools.category', compact('category', 'cat', 'tools', 'scripts'));
    }

    public function process(
        Request $request,
        ImageProcessor $imageProcessor,
        TextProcessor $textProcessor,
        ZipProcessor $zipProcessor
    ): Response|JsonResponse {
        $category = (string) $request->input('category', '');
        $toolKey  = (string) $request->input('tool', '');
        $tool     = ToolCatalog::categories()[$category]['tools'][$toolKey] ?? null;

        if (!$tool || $tool['engine'] !== 'server') {
            return response()->json(['error' => 'أداة غير معروفة.'], 400);
        }

        [$opts, $errors] = ToolCatalog::validateFields($tool['fields'], $request->all());

        $files = $request->file('files', []);
        if (!is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $multi = $tool['multi'] ?? false;
        if (empty($files)) {
            $errors[] = 'اختر ملفاً أولاً.';
        } elseif (!$multi && count($files) > 1) {
            $errors[] = 'هذه الأداة تقبل ملفاً واحداً فقط.';
        } elseif (count($files) > 20) {
            $errors[] = 'الحد الأقصى 20 ملفاً في المرة الواحدة.';
        }

        $maxBytes = ToolCatalog::maxUploadBytes(Auth::check());

        foreach ($files as $f) {
            if (!$f->isValid()) {
                $errors[] = 'تعذّر رفع الملف: ' . $f->getErrorMessage();
            } elseif ($f->getSize() > $maxBytes) {
                $errors[] = __('حجم الملف يتجاوز الحد الأقصى (:size).', ['size' => ToolCatalog::formatBytes($maxBytes)]);
            } elseif ($tool['accept'] && !in_array(strtolower($f->getClientOriginalExtension()), $tool['accept'], true)) {
                $errors[] = 'صيغة «' . $f->getClientOriginalName() . '» غير مدعومة. الصيغ المقبولة: ' . implode('، ', $tool['accept']);
            }
        }

        if ($errors) {
            return response()->json(['error' => implode("\n", $errors)], 422);
        }

        try {
            $result = match ($category) {
                'image'    => $imageProcessor->process($toolKey, $files[0], $opts),
                'document' => $toolKey === 'zip'
                    ? $zipProcessor->process($files, $opts)
                    : $textProcessor->process($toolKey, $files[0], $opts),
            };
        } catch (ToolException $ex) {
            return response()->json(['error' => $ex->getMessage()], 422);
        } catch (\Throwable $ex) {
            Log::error('Tool processing failed', ['exception' => $ex]);
            return response()->json(['error' => 'حدث خطأ غير متوقع أثناء المعالجة.'], 500);
        }

        Operation::create([
            'user_id'   => Auth::id(),
            'category'  => $category,
            'tool'      => $toolKey,
            'file_name' => mb_substr($files[0]->getClientOriginalName(), 0, 255),
        ]);

        if (isset($result['json'])) {
            return response()->json($result['json']);
        }

        $headers = [
            'Content-Type'  => $result['mime'],
            'X-File-Name'   => rawurlencode($result['name']),
            'Cache-Control' => 'no-store',
        ];
        if (!empty($result['info'])) {
            $headers['X-Info'] = rawurlencode($result['info']);
        }

        return response($result['data'], 200, $headers);
    }

    public function log(Request $request): JsonResponse
    {
        $category = (string) $request->input('category', '');
        $toolKey  = (string) $request->input('tool', '');
        $fileName = (string) $request->input('file_name', '');

        if (!isset(ToolCatalog::categories()[$category]['tools'][$toolKey])) {
            return response()->json(['ok' => false], 400);
        }

        Operation::create([
            'user_id'   => Auth::id(),
            'category'  => $category,
            'tool'      => $toolKey,
            'file_name' => $fileName !== '' ? mb_substr($fileName, 0, 255) : null,
        ]);

        return response()->json(['ok' => true]);
    }
}

