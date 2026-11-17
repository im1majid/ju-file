<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Share;
use App\Services\ExternalHostService;
use App\Services\ToolCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareController extends Controller
{
    public function create(): View
    {
        $user = Auth::user();
        $expiryOptions = ToolCatalog::expiryOptions((bool) $user);
        $recent = $user
            ? $user->shares()->active()->latest('id')->limit(5)->get()
            : collect();

        return view('shares.create', compact('expiryOptions', 'recent'));
    }

    public function store(Request $request, ExternalHostService $externalHost): JsonResponse|RedirectResponse
    {
        $ajax = $request->boolean('ajax');
        $user = Auth::user();
        $expiryOptions = ToolCatalog::expiryOptions((bool) $user);

        $this->cleanupExpired();

        $validator = Validator::make($request->all(), [
            'title'    => ['nullable', 'string', 'max:150'],
            'expiry'   => ['required', 'in:' . implode(',', array_keys($expiryOptions))],
            'password' => ['nullable', 'string', 'min:4', 'max:72'],
            'file'     => ['required', 'file', 'max:' . (int) (ToolCatalog::MAX_UPLOAD_BYTES / 1024)],
        ], [
            'title.max'     => 'العنوان يجب ألا يتجاوز 150 حرفاً.',
            'expiry.in'     => 'مدة الصلاحية غير صالحة' . ($user ? '.' : ' (سجّل دخولك للمدد الأطول).'),
            'password.min'  => 'كلمة مرور الرابط يجب أن تكون 4 أحرف على الأقل.',
            'password.max'  => 'كلمة مرور الرابط طويلة جداً.',
            'file.required' => 'اختر ملفاً للمشاركة.',
            'file.file'     => 'الملف غير صالح.',
            'file.max'      => 'حجم الملف يتجاوز الحد الأقصى المسموح.',
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
        $originalName = ToolCatalog::cleanFilename($file->getClientOriginalName());
        $externalUrl = $externalHost->upload($storedPath, $originalName);

        $token = bin2hex(random_bytes(8));
        $expiry = (string) $request->input('expiry', '24');
        $hours = $expiry === 'never' ? 0 : (int) $expiry;
        $password = (string) $request->input('password', '');
        $title = trim((string) $request->input('title', ''));

        $share = Share::create([
            'user_id'       => $user?->id,
            'token'         => $token,
            'title'         => $title !== '' ? $title : null,
            'original_name' => $originalName,
            'stored_name'   => $stored,
            'external_url'  => $externalUrl,
            'mime'          => $mime,
            'size'          => filesize($storedPath) ?: 0,
            'category'      => ToolCatalog::categoryOfMime($mime),
            'password_hash' => $password !== '' ? Hash::make($password) : null,
            'expires_at'    => $hours > 0 ? now()->addHours($hours) : null,
        ]);

        Operation::create([
            'user_id'   => $user?->id,
            'category'  => 'share',
            'tool'      => 'link',
            'file_name' => mb_substr($originalName, 0, 255),
        ]);

        $publicUrl = $share->publicUrl();
        if ($ajax) {
            return response()->json(['url' => $publicUrl, 'token' => $token]);
        }

        return redirect()->route('shares.show', $token)
            ->with('success', 'تم رفع الملف وإنشاء رابط المشاركة العام ورمز QR بنجاح!');
    }

    public function show(Request $request, ?string $token = null): View
    {
        $token = $token ?? (string) $request->query('t', '');
        $share = $this->findByToken($token);

        if (!$share || $share->isExpired()) {
            return view('shares.show', [
                'share'       => $share,
                'expired'     => (bool) $share,
                'unlocked'    => false,
                'isOwner'     => false,
                'previewable' => null,
            ]);
        }

        $unlocked = !$share->password_hash
            || (Auth::check() && Auth::id() === $share->user_id)
            || (bool) session("unlocked.{$share->token}", false);

        $isOwner = Auth::check() && Auth::id() === $share->user_id;
        $mime = $share->mime;
        $previewable = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) ? 'img'
            : (str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') ? 'audio' : null));

        return view('shares.show', [
            'share'       => $share,
            'expired'     => false,
            'unlocked'    => $unlocked,
            'isOwner'     => $isOwner,
            'previewable' => $previewable,
        ]);
    }

    public function unlock(Request $request, string $token): RedirectResponse
    {
        $share = $this->findByToken($token);
        if ($share && !$share->isExpired()) {
            if (Hash::check((string) $request->input('password', ''), (string) $share->password_hash)) {
                session(["unlocked.{$share->token}" => true]);
                return redirect()->route('shares.show', $share->token)->with('success', 'تم فتح الملف.');
            }
            usleep(400000);
            return redirect()->route('shares.show', $share->token)->with('danger', 'كلمة المرور غير صحيحة.');
        }

        return redirect()->route('shares.show', $token);
    }

    public function download(Request $request, ?string $token = null): BinaryFileResponse|RedirectResponse
    {
        $token = $token ?? (string) $request->query('t', '');
        $share = $this->findByToken($token);
        abort_if(!$share || $share->isExpired(), 404, 'الملف غير موجود أو انتهت صلاحيته.');

        $unlocked = !$share->password_hash
            || (Auth::check() && Auth::id() === $share->user_id)
            || (bool) session("unlocked.{$share->token}", false);
        abort_unless($unlocked, 403, 'هذا الملف محمي بكلمة مرور.');

        $inline = $request->boolean('inline') && preg_match('#^(image/(jpeg|png|gif|webp)|video/|audio/)#', $share->mime);
        $path = storage_path('app/uploads/' . $share->stored_name);

        if (!is_file($path)) {
            if ($share->external_url) {
                if (!$inline) {
                    $share->increment('downloads');
                }
                return redirect()->away($share->external_url);
            }
            abort(404, 'الملف غير موجود على الخادم.');
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

        if ($data['expiry'] === 'never') {
            $share->expires_at = null;
        } elseif ($data['expiry'] !== 'keep') {
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

    private function cleanupExpired(): void
    {
        if (random_int(1, 20) !== 1) {
            return;
        }
        Share::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->limit(25)
            ->get()
            ->each(function (Share $share) {
                @unlink(storage_path('app/uploads/' . $share->stored_name));
                $share->delete();
            });
    }
}

