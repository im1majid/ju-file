<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

// تبديل اللغة (العربية / الإنجليزية)
Route::get('/lang/{locale}', function (string $locale) {
    session(['locale' => $locale]);
    Cookie::queue('ju_locale', $locale, 60 * 24 * 365);
    return redirect()->back(fallback: route('home'));
})->whereIn('locale', ['ar', 'en'])->name('locale.switch');

// الصفحة الرئيسية
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::redirect('/index.php', '/');

// صفحات أدوات الملفات حسب النوع
foreach (['image', 'video', 'audio', 'document'] as $cat) {
    Route::get("/{$cat}", [ToolController::class, 'category'])
        ->defaults('category', $cat)
        ->name("tools.{$cat}");
    Route::redirect("/{$cat}.php", "/{$cat}");
}

// واجهة معالجة الملفات وتسجيل العمليات
Route::post('/api/process', [ToolController::class, 'process'])->name('api.process');
Route::post('/api/process.php', [ToolController::class, 'process']);
Route::post('/api/log', [ToolController::class, 'log'])->name('api.log');
Route::post('/api/log.php', [ToolController::class, 'log']);

// المشاركة السريعة (رابط + QR)
Route::get('/share', [ShareController::class, 'create'])->name('shares.create');
Route::post('/share', [ShareController::class, 'store'])->name('shares.store');
Route::redirect('/share.php', '/share');
Route::post('/share.php', [ShareController::class, 'store']);

Route::get('/f/{token}', [ShareController::class, 'show'])->where('token', '[a-f0-9]{16}')->name('shares.show');
Route::post('/f/{token}', [ShareController::class, 'unlock'])->where('token', '[a-f0-9]{16}')->name('shares.unlock');
Route::get('/f.php', [ShareController::class, 'show']);

Route::get('/download/{token}', [ShareController::class, 'download'])->where('token', '[a-f0-9]{16}')->name('shares.download');
Route::get('/download.php', [ShareController::class, 'download']);

// المصادقة (تسجيل الدخول وإنشاء الحساب)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::redirect('/login.php', '/login');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::redirect('/register.php', '/register');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// صفحات المستخدم المسجّل
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::redirect('/dashboard.php', '/dashboard');
    Route::delete('/dashboard/shares/{id}', [DashboardController::class, 'destroyShare'])->name('shares.destroy');
    Route::delete('/dashboard/history', [DashboardController::class, 'clearHistory'])->name('history.clear');

    Route::get('/shares/{id}/edit', [ShareController::class, 'edit'])->name('shares.edit');
    Route::put('/shares/{id}', [ShareController::class, 'update'])->name('shares.update');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::redirect('/account.php', '/account');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
});
