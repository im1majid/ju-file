@extends('layouts.app')

@section('title', $share ? ($share->title ?: $share->original_name) : 'ملف غير موجود')

@section('content')
@if(!$share || $expired)
    <div class="panel text-center py-5 mx-auto" style="max-width:520px">
        <i class="bi bi-file-earmark-x display-4 text-secondary"></i>
        <h1 class="h4 fw-bold mt-3">{{ $share ? 'انتهت صلاحية هذا الرابط' : 'الملف غير موجود' }}</h1>
        <p class="text-secondary">ربما حُذف الملف أو انتهت مدته.</p>
        <a class="btn btn-accent" href="{{ route('shares.create') }}">شارك ملفاً جديداً</a>
    </div>
@else
    @php
        $dl = route('shares.download', $share->token);
    @endphp
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="panel">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="file-icon"><i class="bi {{ \App\Services\ToolCatalog::categoryIcon($share->category) }}"></i></div>
                    <div class="min-w-0">
                        <h1 class="h5 fw-bold mb-0 text-truncate">{{ $share->title ?: $share->original_name }}</h1>
                        <div class="small text-secondary text-truncate">{{ $share->original_name }}</div>
                    </div>
                </div>

                @if(!$unlocked)
                    <form method="post" action="{{ route('shares.unlock', $share->token) }}" class="border rounded-3 p-3 bg-light">
                        @csrf
                        <label class="form-label fw-bold" for="password"><i class="bi bi-lock"></i> هذا الملف محمي بكلمة مرور</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <button class="btn btn-accent">فتح</button>
                        </div>
                    </form>
                @else
                    @if($previewable === 'img')
                        <img class="preview-media mb-3" src="{{ $dl }}?inline=1" alt="{{ $share->original_name }}">
                    @elseif($previewable === 'video')
                        <video class="preview-media mb-3" src="{{ $dl }}?inline=1" controls preload="metadata"></video>
                    @elseif($previewable === 'audio')
                        <audio class="w-100 mb-3" src="{{ $dl }}?inline=1" controls preload="metadata"></audio>
                    @endif
                    <a class="btn btn-accent btn-lg w-100" href="{{ $dl }}">
                        <i class="bi bi-download"></i> تنزيل ({{ \App\Services\ToolCatalog::formatBytes($share->size) }})
                    </a>
                @endif

                <dl class="meta-list mt-4 mb-0">
                    <div><dt>النوع</dt><dd>{{ \App\Services\ToolCatalog::categoryLabel($share->category) }} · <span dir="ltr">{{ $share->mime }}</span></dd></div>
                    <div><dt>الحجم</dt><dd>{{ \App\Services\ToolCatalog::formatBytes($share->size) }}</dd></div>
                    <div><dt>التنزيلات</dt><dd>{{ $share->downloads }}</dd></div>
                    <div><dt>تاريخ الرفع</dt><dd dir="ltr">{{ $share->created_at?->format('Y-m-d H:i') }}</dd></div>
                    <div><dt>ينتهي</dt><dd dir="ltr">{{ $share->expires_at?->format('Y-m-d H:i') ?? 'بدون انتهاء' }}</dd></div>
                </dl>

                @if($isOwner)
                    <div class="d-flex gap-2 mt-3">
                        <a class="btn btn-light btn-sm" href="{{ route('shares.edit', $share->id) }}"><i class="bi bi-pencil"></i> تعديل</a>
                        <a class="btn btn-light btn-sm" href="{{ route('dashboard') }}"><i class="bi bi-grid"></i> ملفاتي</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-qr-code-scan text-accent"></i> المشاركة بالرابط أو رمز QR</h2>
                <x-share-box :url="$share->publicUrl()" />
            </div>
        </div>
    </div>
@endif
@endsection

