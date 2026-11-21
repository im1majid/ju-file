@extends('layouts.app')

@section('title', __('كل أدوات ملفاتك في مكان واحد'))
@php $activePage = 'home'; @endphp

@section('content')
<section class="hero text-center py-4 py-lg-5 mb-4">
    <span class="badge rounded-pill bg-accent-soft text-accent px-3 py-2 mb-3">
        <i class="bi bi-magic"></i> {{ __('بسيط · سريع · آمن') }}
    </span>
    <h1 class="display-6 fw-bold mb-3">{{ __('اختر نوع الملف وابدأ التعديل فوراً') }}</h1>
    <p class="lead text-secondary mx-auto mb-4" style="max-width:640px">
        {{ __('منصة متخصصة لتعديل الملفات، تحويل الصيغ، وتصغير الحجم — مع إمكانية تحويل أي ملف إلى رابط مباشر أو رمز QR للمشاركة السريعة.') }}
    </p>
    <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="{{ route('shares.create') }}" class="btn btn-accent btn-lg px-4">
            <i class="bi bi-qr-code"></i> {{ __('حوّل ملفاً إلى رابط أو QR') }}
        </a>
        <a href="{{ route('social.download') }}" class="btn btn-outline-dark btn-lg px-4">
            <i class="bi bi-cloud-arrow-down text-accent"></i> {{ __('تحميل المقاطع والصور') }}
        </a>
        @guest
            <a href="{{ route('register') }}" class="btn btn-outline-dark btn-lg px-4">{{ __('حساب مجاني لحفظ روابطك') }}</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-grid"></i> {{ __('لوحة ملفاتي') }}</a>
        @endguest
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">{{ __('ماذا تريد أن تعدّل اليوم؟') }}</h2>
        <span class="small text-secondary">{{ __('تظهر لك الخيارات المتاحة لكل نوع ملف تحديداً') }}</span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5 g-3">
        @foreach($cats as $key => $cat)
            <div class="col">
                <a class="cat-card panel h-100 d-flex flex-column text-decoration-none text-reset" href="{{ route('tools.' . $key) }}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="cat-icon"><i class="bi {{ $cat['icon'] }}"></i></div>
                        <span class="badge text-bg-light">{{ __(':count أدوات', ['count' => count($cat['tools'])]) }}</span>
                    </div>
                    <h3 class="h5 fw-bold mb-1">{{ $cat['title'] }}</h3>
                    <p class="small text-secondary mb-3 flex-grow-1">{{ $cat['desc'] }}</p>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @foreach($cat['tools'] as $t)
                            <span class="mini-tag">{{ $t['title'] }}</span>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between align-items-center small fw-bold text-accent mt-auto pt-2 border-top">
                        <span>{{ __('فتح أدوات :title', ['title' => $cat['title']]) }}</span>
                        <i class="bi bi-arrow-left icon-directional"></i>
                    </div>
                </a>
            </div>
        @endforeach

        <div class="col">
            <a class="cat-card panel h-100 d-flex flex-column text-decoration-none text-reset" href="{{ route('social.download') }}">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="cat-icon"><i class="bi bi-cloud-arrow-down"></i></div>
                    <span class="badge text-bg-light">{{ __(':count منصات', ['count' => 5]) }}</span>
                </div>
                <h3 class="h5 fw-bold mb-1">{{ __('تحميل الصور والمقاطع') }}</h3>
                <p class="small text-secondary mb-3 flex-grow-1">{{ __('حمّل الفيديوهات والصور واستخرج الصوت بأعلى جودة من منصات التواصل.') }}</p>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <span class="mini-tag">YouTube</span>
                    <span class="mini-tag">Instagram</span>
                    <span class="mini-tag">TikTok</span>
                    <span class="mini-tag">X (Twitter)</span>
                    <span class="mini-tag">Facebook</span>
                    <span class="mini-tag">{{ __('استخراج الصوت') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small fw-bold text-accent mt-auto pt-2 border-top">
                    <span>{{ __('فتح أدوات التحميل') }}</span>
                    <i class="bi bi-arrow-left icon-directional"></i>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="panel mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <h2 class="h5 fw-bold mb-2"><i class="bi bi-qr-code-scan text-accent"></i> {{ __('مشاركة أي ملف برابط أو رمز QR') }}</h2>
            <p class="text-secondary mb-3">
                {{ __('سواء عدّلت ملفك هنا أو كان لديك ملف جاهز على جهازك، يمكنك تحويله خلال ثوانٍ إلى رابط قصير ورمز QR قابل للمسح بالجوال، مع خيار تحديد مدة صلاحية أو كلمة مرور.') }}
            </p>
            <a href="{{ route('shares.create') }}" class="btn btn-accent"><i class="bi bi-upload"></i> {{ __('ارفع ملفاً للمشاركة') }}</a>
        </div>
        <div class="col-lg-5">
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent">{{ $totalOps }}</div>
                        <div class="small text-secondary">{{ __('عملية معالجة منفّذة') }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent">{{ $totalShares }}</div>
                        <div class="small text-secondary">{{ __('رابط مشاركة نشط') }}</div>
                    </div>
                </div>
                @foreach($cats as $key => $cat)
                    <div class="col-3">
                        <div class="p-2 rounded-3 bg-light">
                            <div class="fw-bold">{{ (int) ($catCounts[$key] ?? 0) }}</div>
                            <div class="small text-secondary">{{ $cat['title'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
