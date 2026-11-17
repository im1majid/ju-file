@extends('layouts.app')

@section('title', 'مشاركة ملف برابط أو QR')
@php $activePage = 'share'; @endphp

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-7">
        <div class="panel">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-qr-code text-accent"></i> تحويل أي ملف إلى رابط ورمز QR</h1>
            <p class="text-secondary small mb-4">ارفع صورة، فيديو، صوت، PDF أو أي ملف لتحصل فوراً على رابط مشاركة عام ورمز QR قابل للمسح.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('shares.store') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <label class="dropzone mb-3" for="shareFile" data-simple-dropzone>
                    <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                    <div class="fw-bold mt-2">اسحب أي ملف هنا أو اضغط للاختيار</div>
                    <div class="small text-secondary mt-1" data-file-label>
                        الحد الأقصى {{ \App\Services\ToolCatalog::formatBytes(\App\Services\ToolCatalog::MAX_UPLOAD_BYTES) }} · جميع الصيغ مدعومة
                    </div>
                    <input class="d-none" type="file" id="shareFile" name="file" required>
                </label>

                <div class="mb-3">
                    <label class="form-label" for="title">عنوان للملف (اختياري)</label>
                    <input class="form-control" id="title" name="title" maxlength="150"
                           value="{{ old('title') }}" placeholder="مثال: عرض التخرج النهائي">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="form-label" for="expiry">مدة الصلاحية</label>
                        <select class="form-select" id="expiry" name="expiry">
                            @foreach($expiryOptions as $k => $label)
                                <option value="{{ $k }}" @selected(old('expiry', '24') === (string) $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @guest
                            <div class="form-text">سجّل دخولك للحصول على مدة 30 يوماً أو بدون انتهاء.</div>
                        @endguest
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password">كلمة مرور للحماية (اختياري)</label>
                        <input class="form-control" type="password" id="password" name="password"
                               minlength="4" maxlength="72" placeholder="اتركه فارغاً للوصول العام">
                    </div>
                </div>

                <button class="btn btn-accent btn-lg w-100"><i class="bi bi-link-45deg"></i> إنشاء الرابط ورمز QR</button>
            </form>
        </div>
    </div>

    @auth
        @if($recent->isNotEmpty())
            <div class="col-lg-4">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">آخر مشاركاتك</h2>
                        <a href="{{ route('dashboard') }}" class="small">عرض الكل</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($recent as $r)
                            <a class="list-group-item list-group-item-action px-0 d-flex align-items-center gap-2"
                               href="{{ route('shares.show', $r->token) }}">
                                <i class="bi {{ \App\Services\ToolCatalog::categoryIcon($r->category) }} text-accent"></i>
                                <span class="text-truncate flex-grow-1">{{ $r->title ?: $r->original_name }}</span>
                                <i class="bi bi-chevron-left small text-secondary"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endauth
</div>
@endsection

