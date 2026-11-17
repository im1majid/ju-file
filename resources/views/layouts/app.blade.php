@php
    use App\Services\ToolCatalog;
    $navCategories = ToolCatalog::categories();
@endphp
<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css?v=7') }}" rel="stylesheet">
    <script>
        (() => {
            const t = localStorage.getItem('ju_theme') === 'light' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}"><i class="bi bi-folder2-open text-accent"></i> {{ config('app.name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="القائمة">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @foreach($navCategories as $navKey => $navCat)
                    <li class="nav-item">
                        <a class="nav-link {{ ($activePage ?? '') === $navKey ? 'active' : '' }}" href="{{ route('tools.' . $navKey) }}">
                            <i class="bi {{ $navCat['icon'] }}"></i> {{ $navCat['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-accent btn-sm" href="{{ route('shares.create') }}"><i class="bi bi-qr-code"></i> مشاركة ملف</a>
                @auth
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-grid"></i> ملفاتي</a></li>
                            <li><a class="dropdown-item" href="{{ route('account') }}"><i class="bi bi-gear"></i> الحساب</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="btn btn-light btn-sm {{ ($activePage ?? '') === 'login' ? 'active' : '' }}" href="{{ route('login') }}">دخول</a>
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('register') }}">حساب جديد</a>
                @endauth
                <button type="button" id="themeToggle" class="btn btn-light btn-sm theme-toggle" title="تبديل المظهر (داكن / فاتح)" aria-label="تبديل المظهر">
                    <i class="bi bi-sun"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    @foreach(['success', 'danger', 'warning', 'info'] as $flashType)
        @if(session($flashType))
            <div class="alert alert-{{ $flashType }} alert-dismissible fade show" role="alert">
                {{ session($flashType) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif
    @endforeach

    @yield('content')
</main>

<footer class="border-top py-4 mt-5 bg-white">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
        <div><i class="bi bi-folder2-open text-accent"></i> <strong>{{ config('app.name') }}</strong> — كل أدوات ملفاتك في مكان واحد</div>
        <nav class="d-flex gap-3">
            @foreach($navCategories as $navKey => $footerCat)
                <a class="link-secondary text-decoration-none" href="{{ route('tools.' . $navKey) }}">{{ $footerCat['title'] }}</a>
            @endforeach
            <a class="link-secondary text-decoration-none" href="{{ route('shares.create') }}">المشاركة</a>
        </nav>
        <div>&copy; {{ date('Y') }}</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/vendor/qrcode.js') }}"></script>
<script src="{{ asset('assets/js/app.js?v=3') }}"></script>
@stack('scripts')
</body>
</html>

