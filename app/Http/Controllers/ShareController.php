<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Share;
use App\Services\ExternalHostService;
use App\Services\ToolCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareController extends Controller
{
    public function create(): View
    {
        $user = Auth::user();
        $expiryOptions = ToolCatalog::expiryOptions((bool) $user);
        $maxUploadBytes = ToolCatalog::maxUploadBytes((bool) $user);
        $recent = $user
            ? $user->shares()->active()->latest('id')->limit(5)->get()
            : collect();

        return view('shares.create', compact('expiryOptions', 'maxUploadBytes', 'recent'));
    }

    public function store(Request $request, ExternalHostService $externalHost): JsonResponse|RedirectResponse
    {
        $ajax = $request->boolean('ajax');
        $user = Auth::user();
        $expiryOptions = ToolCatalog::expiryOptions((bool) $user);
        $maxBytes = ToolCatalog::maxUploadBytes((bool) $user);

        $this->cleanupExpired($externalHost);

        $validator = Validator::make($request->all(), [
            'title'    => ['nullable', 'string', 'max:150'],
            'expiry'   => ['required', 'in:' . implode(',', array_keys($expiryOptions))],
            'password' => ['nullable', 'string', 'min:4', 'max:72'],
            'file'     => ['required', 'file', 'max:' . (int) ($maxBytes / 1024)],
        ], [
            'title.max'     => __('العنوان يجب ألا يتجاوز 150 حرفاً.'),
            'expiry.in'     => __('مدة الصلاحية غير صالحة') . ($user ? '.' : ' ' . __('(سجّل دخولك للمدد الأطول).')),
            'password.min'  => __('كلمة مرور الرابط يجب أن تكون 4 أحرف على الأقل.'),
            'password.max'  => __('كلمة مرور الرابط طويلة جداً.'),
            'file.required' => __('اختر ملفاً للمشاركة.'),
            'file.file'     => __('الملف غير صالح.'),
            'file.max'      => __('حجم الملف يتجاوز الحد الأقصى المسموح (:size).', ['size' => ToolCatalog::formatBytes($maxBytes)]),
        ]);

        if ($validator->fails()) {
            $msgs = $validator->errors()->all();
            if ($ajax) {
                return response()->json(['error' => implode("\n", $msgs)], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');
        $uploadDir = storage_path('app/uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $stored = bin2hex(random_bytes(16));
        $file->move($uploadDir, $stored);
        $storedPath = $uploadDir . DIRECTORY_SEPARATOR . $stored;

        $mime = mime_content_type($storedPath) ?: ($file->getClientMimeType() ?: 'application/octet-stream');
        $fileSize = filesize($storedPath) ?: 0;
        $originalName = ToolCatalog::cleanFilename($file->getClientOriginalName());
        $uploaded = $externalHost->upload($storedPath, $originalName);

        // حذف النسخة المحلية فوراً إذا نجح الرفع إلى Cloudinary حتى لا يمتلئ قرص Railway
        if ($uploaded && $uploaded['is_cloudinary']) {
            @unlink($storedPath);
        }

        $token = bin2hex(random_bytes(8));
        $hours = max(1, (int) $request->input('expiry', '24'));
        $password = (string) $request->input('password', '');
        $title = trim((string) $request->input('title', ''));

        $share = Share::create([
            'user_id'                  => $user?->id,
            'token'                    => $token,
            'title'                    => $title !== '' ? $title : null,
            'original_name'            => $originalName,
            'stored_name'              => $stored,
            'external_url'             => $uploaded['url'] ?? null,
            'cloudinary_public_id'     => $uploaded['public_id'] ?? null,
            'cloudinary_resource_type' => $uploaded['resource_type'] ?? null,
            'mime'                     => $mime,
            'size'                     => $fileSize,
            'category'                 => ToolCatalog::categoryOfMime($mime),
            'password_hash'            => $password !== '' ? Hash::make($password) : null,
            'expires_at'               => now()->addHours($hours),
        ]);

        Operation::create([
            'user_id'   => $user?->id,
            'category'  => 'share',
            'tool'      => 'link',
            'file_name' => mb_substr($originalName, 0, 255),
        ]);

        $publicUrl = $share->publicUrl();
        if ($ajax) {
            return response()->json([
                'url'      => $publicUrl,
                'token'    => $token,
                'show_url' => route('shares.show', $token),
            ]);
        }

        return redirect()->route('shares.show', $token)
            ->with('success', __('تم رفع الملف وإنشاء رابط المشاركة العام ورمز QR بنجاح!'));
    }

    public function show(Request $request, ?string $token = null): View
    {
        $rawToken = trim((string) ($token ?? $request->query('t', '')));
        $remembered = (string) $request->session()->get('pending_share_token', '');
        $resolvedToken = $rawToken !== '' ? $rawToken : $remembered;
        $share = $this->findByToken($resolvedToken);

        // إذا حذف المستخدم الرمز من الرابط ولا يوجد ملف مقيّد بالجلسة، نظهر شاشة القفل (الرمز + كلمة المرور)
        if ($rawToken === '' && (!$share || $share->isExpired())) {
            return view('shares.show', [
                'share'       => null,
                'expired'     => false,
                'needsLock'   => true,
                'unlocked'    => false,
                'isOwner'     => false,
                'previewable' => null,
            ]);
        }

        if (!$share || $share->isExpired()) {
            return view('shares.show', [
                'share'       => $share,
                'expired'     => (bool) $share,
                'needsLock'   => false,
                'unlocked'    => false,
                'isOwner'     => false,
                'previewable' => null,
            ]);
        }

        // حفظ رمز الملف المحمي في الجلسة حتى لو حذف المستخدم الرمز من الرابط يظل يرى صفحة القفل
        if ($share->password_hash) {
            $request->session()->put('pending_share_token', $share->token);
        }

        // الحماية الحقيقية على السيرفر: لا يُفتح الملف المحمي إلا بعد التحقق من كلمة المرور عبر الجلسة
        $unlocked = !$share->password_hash
            || (bool) session("unlocked.{$share->token}", false);

        $isOwner = Auth::check() && Auth::id() === $share->user_id;
        $mime = $share->mime;
        $previewable = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) ? 'img'
            : (str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') ? 'audio' : null));

        return view('shares.show', [
            'share'       => $share,
            'expired'     => false,
            'needsLock'   => false,
            'unlocked'    => $unlocked,
            'isOwner'     => $isOwner,
            'previewable' => $previewable,
        ]);
    }

    public function unlock(Request $request, ?string $token = null): RedirectResponse
    {
        $resolved = trim((string) ($token ?? $request->input('token', '') ?: $request->session()->get('pending_share_token', '')));

        $request->validate([
            'password' => ['required', 'string', 'max:72'],
        ], [
            'password.required' => __('أدخل كلمة المرور لفتح الملف.'),
        ]);

        $share = $this->findByToken($resolved);
        if (!$share || $share->isExpired()) {
            return back()->withInput($request->only('token'))
                ->withErrors(['token' => __('رمز الملف غير صالح أو انتهت صلاحيته.')]);
        }

        if ($share->password_hash && !Hash::check((string) $request->input('password', ''), (string) $share->password_hash)) {
            usleep(400000);
            return redirect()->route('shares.show', $share->token)
                ->withErrors(['password' => __('كلمة المرور غير صحيحة.')]);
        }

        session(["unlocked.{$share->token}" => true]);
        return redirect()->route('shares.show', $share->token)->with('success', __('تم فتح الملف.'));
    }

    public function download(Request $request, ?string $token = null): BinaryFileResponse|RedirectResponse|Response
    {
        $rawToken = trim((string) ($token ?? $request->query('t', '')));
        $resolved = $rawToken !== '' ? $rawToken : (string) $request->session()->get('pending_share_token', '');
        $share = $this->findByToken($resolved);
        abort_if(!$share || $share->isExpired(), 404, __('الملف غير موجود أو انتهت صلاحيته.'));

        // منع تنزيل الملف من السيرفر ما لم يتم التحقق من كلمة المرور فعلياً
        $unlocked = !$share->password_hash
            || (bool) session("unlocked.{$share->token}", false);
        abort_unless($unlocked, 403, __('هذا الملف محمي بكلمة مرور.'));

        $inline = $request->boolean('inline') && preg_match('#^(image/(jpeg|png|gif|webp)|video/|audio/)#', $share->mime);
        $path = storage_path('app/uploads/' . $share->stored_name);

        if (!is_file($path)) {
            if ($share->external_url) {
                if (!$inline) {
                    $share->increment('downloads');
                }
                // للملفات المحمية بكلمة مرور: نمرّر الملف عبر الخادم بدون كشف رابط Cloudinary المباشر
                if ($share->password_hash) {
                    $remote = Http::timeout(60)->get($share->external_url);
                    abort_unless($remote->successful(), 404, __('تعذّر جلب الملف من التخزين السحابي.'));
                    $safeName = str_replace(['"', "\r", "\n"], '', $share->original_name);
                    return response($remote->body(), 200, [
                        'Content-Type'           => $share->mime,
                        'Content-Disposition'    => ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"',
                        'X-Content-Type-Options' => 'nosniff',
                        'Cache-Control'          => 'private, no-store',
                    ]);
                }
                return redirect()->away($share->external_url);
            }
            abort(404, __('الملف غير موجود على الخادم.'));
        }

        if (!$inline) {
            $share->increment('downloads');
        }

        $safeName = str_replace(['"', "\r", "\n"], '', $share->original_name);
        return response()->file($path, [
            'Content-Type'           => $share->mime,
            'Content-Disposition'    => ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        $share = Share::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$share) {
            return redirect()->route('dashboard')->with('danger', 'الملف غير موجود.');
        }

        $expiryOptions = ['keep' => 'بدون تغيير'] + ToolCatalog::expiryOptions(true);
        return view('shares.edit', compact('share', 'expiryOptions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $share = Share::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$share) {
            return redirect()->route('dashboard')->with('danger', 'الملف غير موجود.');
        }

        $expiryOptions = ['keep' => 'بدون تغيير'] + ToolCatalog::expiryOptions(true);
        $data = $request->validate([
            'title'         => ['nullable', 'string', 'max:150'],
            'expiry'        => ['required', 'in:' . implode(',', array_keys($expiryOptions))],
            'password_mode' => ['required', 'in:keep,set,remove'],
            'password'      => ['required_if:password_mode,set', 'nullable', 'string', 'min:4', 'max:72'],
        ], [
            'title.max'            => 'العنوان يجب ألا يتجاوز 150 حرفاً.',
            'password.required_if'=> 'أدخل كلمة المرور الجديدة.',
            'password.min'         => 'كلمة المرور الجديدة يجب أن تكون 4 أحرف على الأقل.',
        ]);

        $title = trim((string) ($data['title'] ?? ''));
        $share->title = $title !== '' ? $title : null;

        if ($data['expiry'] !== 'keep') {
            $share->expires_at = now()->addHours((int) $data['expiry']);
        }

        if ($data['password_mode'] === 'set') {
            $share->password_hash = Hash::make($data['password']);
        } elseif ($data['password_mode'] === 'remove') {
            $share->password_hash = null;
        }

        $share->save();

        return redirect()->route('dashboard')->with('success', 'تم حفظ التعديلات.');
    }

    private function findByToken(string $token): ?Share
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $token)) {
            return null;
        }
        return Share::where('token', $token)->first();
    }

    private function cleanupExpired(ExternalHostService $externalHost): void
    {
        if (random_int(1, 10) !== 1) {
            return;
        }
        Share::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->limit(25)
            ->get()
            ->each(function (Share $share) use ($externalHost) {
                $externalHost->purgeShareAssets($share);
                $share->delete();
            });
    }
}

