<?php

namespace App\Http\Controllers;

use App\Services\ExternalHostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(): View
    {
        return view('account', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
        ], [
            'name.required' => 'الاسم مطلوب.',
            'name.min'      => 'الاسم يجب أن يكون بين 2 و 80 حرفاً.',
            'name.max'      => 'الاسم يجب أن يكون بين 2 و 80 حرفاً.',
        ]);

        Auth::user()->update(['name' => trim($data['name'])]);

        return redirect()->route('account')->with('success', 'تم تحديث الاسم.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $request->validate([
            'current_password'     => ['required', 'string'],
            'new_password'         => ['required', 'string', 'min:8', 'max:72', 'same:new_password_confirm'],
            'new_password_confirm' => ['required', 'string'],
        ], [
            'new_password.min'  => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.',
            'new_password.same' => 'كلمتا المرور غير متطابقتين.',
        ]);

        if (!Hash::check((string) $request->input('current_password'), $user->password_hash)) {
            return back()->withErrors(['password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->update(['password_hash' => Hash::make((string) $request->input('new_password'))]);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'تم تغيير كلمة المرور.');
    }

    public function destroy(Request $request, ExternalHostService $externalHost): RedirectResponse
    {
        $user = Auth::user();
        if (!Hash::check((string) $request->input('current_password', ''), $user->password_hash)) {
            return back()->withErrors(['delete' => 'كلمة المرور غير صحيحة.']);
        }

        foreach ($user->shares()->get() as $share) {
            $externalHost->purgeShareAssets($share);
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'تم حذف حسابك وجميع ملفاتك.');
    }
}

