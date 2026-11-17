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

// خريطة الموقع لمحركات البحث (Google SEO)
Route::get('/sitemap.xml', function () {
    $urls = [
        route('home'),
        route('tools.image'),
        route('tools.video'),
        route('tools.audio'),
        route('tools.document'),
        route('shares.create'),
    ];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
         . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u, ENT_XML1) . '</loc><changefreq>weekly</changefreq></url>' . "\n";
    }
    $xml .= '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

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

// واجهة معالجة الملفات وتسجيل العمليات (مع حماية Rate Limit ضد الإغراق)
Route::post('/api/process', [ToolController::class, 'process'])->middleware('throttle:30,1')->name('api.process');
Route::post('/api/process.php', [ToolController::class, 'process'])->middleware('throttle:30,1');
Route::post('/api/log', [ToolController::class, 'log'])->middleware('throttle:60,1')->name('api.log');
Route::post('/api/log.php', [ToolController::class, 'log'])->middleware('throttle:60,1');

// المشاركة السريعة (رابط + QR) — حد أقصى 10 رفعات في الدقيقة لكل مستخدم/IP
Route::get('/share', [ShareController::class, 'create'])->name('shares.create');
Route::post('/share', [ShareController::class, 'store'])->middleware('throttle:10,1')->name('shares.store');
Route::redirect('/share.php', '/share');
Route::post('/share.php', [ShareController::class, 'store'])->middleware('throttle:10,1');

Route::get('/f/{token}', [ShareController::class, 'show'])->where('token', '[a-f0-9]{16}')->name('shares.show');
Route::post('/f/{token}', [ShareController::class, 'unlock'])->where('token', '[a-f0-9]{16}')->middleware('throttle:10,1')->name('shares.unlock');
Route::get('/f.php', [ShareController::class, 'show']);

Route::get('/download/{token}', [ShareController::class, 'download'])->where('token', '[a-f0-9]{16}')->name('shares.download');
Route::get('/download.php', [ShareController::class, 'download']);

// المصادقة (تسجيل الدخول وإنشاء الحساب)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::redirect('/login.php', '/login');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
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
