@extends('layouts.app')

@section('title', 'ملفاتي')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">ملفاتي وروابطي</h1>
        <p class="text-secondary small mb-0">مرحباً {{ $user->name }} — أدر ملفاتك المشاركة وتابع آخر العمليات.</p>
    </div>
    <a class="btn btn-accent" href="{{ route('shares.create') }}"><i class="bi bi-plus-lg"></i> مشاركة ملف جديد</a>
</div>

<div class="row g-3 mb-4">
    @php
        $statCards = [
            ['bi-files', $stats['files'], 'ملفات نشطة'],
            ['bi-download', $stats['downloads'], 'إجمالي التنزيلات'],
            ['bi-hdd', \App\Services\ToolCatalog::formatBytes($stats['size']), 'المساحة المستخدمة'],
            ['bi-activity', $opsCount, 'عمليات منجزة'],
        ];
    @endphp
    @foreach($statCards as [$icon, $val, $lbl])
        <div class="col-6 col-lg-3">
            <div class="panel stat">
                <i class="bi {{ $icon }}"></i>
                <div>
                    <div class="stat-value">{{ $val }}</div>
                    <div class="small text-secondary">{{ $lbl }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<form class="panel mb-4" method="get" action="{{ route('dashboard') }}">
    <div class="row g-2 align-items-center">
        <div class="col-md-4">
            <input class="form-control" name="q" value="{{ $q }}" placeholder="ابحث بالاسم أو العنوان...">
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="category">
                <option value="">كل الأنواع</option>
                @foreach($categories as $k => $v)
                    <option value="{{ $k }}" @selected($category === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="status">
                <option value="active" @selected($status === 'active')>النشطة فقط</option>
                <option value="all" @selected($status === 'all')>الكل</option>
                <option value="expired" @selected($status === 'expired')>المنتهية</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="sort">
                <option value="newest" @selected($sort === 'newest')>الأحدث</option>
                <option value="oldest" @selected($sort === 'oldest')>الأقدم</option>
                <option value="downloads" @selected($sort === 'downloads')>الأكثر تنزيلاً</option>
                <option value="size" @selected($sort === 'size')>الأكبر حجماً</option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-1">
            <button class="btn btn-accent flex-grow-1">تصفية</button>
            @if($q !== '' || $category !== '' || $status !== 'active' || $sort !== 'newest')
                <a class="btn btn-light" href="{{ route('dashboard') }}" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
            @endif
        </div>
    </div>
</form>

<div class="panel mb-4">
    @if($shares->isEmpty())
        <div class="text-center py-4 text-secondary">
            <i class="bi bi-inbox display-6"></i>
            <p class="mt-2 mb-3">لا توجد ملفات مطابقة.</p>
            <a class="btn btn-accent btn-sm" href="{{ route('shares.create') }}">ارفع أول ملف</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>الملف</th>
                        <th>النوع</th>
                        <th>الحجم</th>
                        <th>التنزيلات</th>
                        <th>الصلاحية</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($shares as $s)
                    @php
                        $link = $s->publicUrl();
                        $isExp = $s->isExpired();
                    @endphp
                    <tr class="{{ $isExp ? 'opacity-50' : '' }}">
                        <td>
                            <a class="fw-bold text-decoration-none text-reset" href="{{ route('shares.show', $s->token) }}">
                                {{ $s->title ?: $s->original_name }}
                            </a>
                            @if($s->password_hash)
                                <i class="bi bi-lock-fill text-secondary small" title="محمي بكلمة مرور"></i>
                            @endif
                            <div class="small text-secondary">{{ $s->original_name }}</div>
                        </td>
                        <td><span class="badge text-bg-light">{{ $categories[$s->category] ?? 'أخرى' }}</span></td>
                        <td class="small">{{ \App\Services\ToolCatalog::formatBytes($s->size) }}</td>
                        <td>{{ $s->downloads }}</td>
                        <td class="small" dir="ltr">
                            {{ $isExp ? 'منتهي' : ($s->expires_at?->format('Y-m-d H:i') ?? 'دائم') }}
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-light btn-sm" data-copy="{{ $link }}" title="نسخ الرابط">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <a class="btn btn-light btn-sm" href="{{ route('shares.show', $s->token) }}" title="عرض و QR">
                                <i class="bi bi-qr-code"></i>
                            </a>
                            <a class="btn btn-light btn-sm" href="{{ route('shares.edit', $s->id) }}" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="{{ route('shares.destroy', $s->id) }}" class="d-inline" data-confirm="حذف هذا الملف ورابطه نهائياً؟">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-light btn-sm text-danger" title="حذف"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if($history->isNotEmpty())
    <div class="panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-clock-history text-accent"></i> آخر 10 عمليات</h2>
            <form method="post" action="{{ route('history.clear') }}" data-confirm="مسح سجل العمليات؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-light btn-sm text-danger">مسح السجل</button>
            </form>
        </div>
        <ul class="list-group list-group-flush">
            @foreach($history as $h)
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2 small">
                    <span>
                        <strong>{{ \App\Services\ToolCatalog::toolTitle($h->category, $h->tool) }}</strong>
                        @if($h->file_name)
                            · <span class="text-secondary">{{ $h->file_name }}</span>
                        @endif
                    </span>
                    <span class="text-secondary text-nowrap" dir="ltr">{{ $h->created_at?->format('Y-m-d H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
@endsection

