@extends('layouts.app')

@section('title', 'تعديل الملف')

@section('content')
<div class="auth-card panel mx-auto" style="max-width:560px">
    <nav class="small mb-2">
        <a href="{{ route('dashboard') }}" class="link-secondary text-decoration-none">ملفاتي</a>
        <span class="text-secondary">/ تعديل</span>
    </nav>
    <h1 class="h5 fw-bold mb-1 text-truncate">{{ $share->original_name }}</h1>
    <p class="small text-secondary mb-4">ينتهي حالياً: <span dir="ltr">{{ $share->expires_at?->format('Y-m-d H:i') ?? 'بدون انتهاء' }}</span></p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('shares.update', $share->id) }}" novalidate>
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label" for="title">العنوان</label>
            <input class="form-control" id="title" name="title" maxlength="150" value="{{ old('title', $share->title) }}">
        </div>
        <div class="mb-3">
            <label class="form-label" for="expiry">تمديد / تغيير الصلاحية (من الآن)</label>
            <select class="form-select" id="expiry" name="expiry">
                @foreach($expiryOptions as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_mode">
                كلمة المرور
                @if($share->password_hash)
                    <span class="badge text-bg-light"><i class="bi bi-lock"></i> مفعّلة</span>
                @endif
            </label>
            <select class="form-select mb-2" id="password_mode" name="password_mode" data-toggle-target="#newPassWrap" data-toggle-value="set">
                <option value="keep">بدون تغيير</option>
                <option value="set">{{ $share->password_hash ? 'تغيير كلمة المرور' : 'إضافة كلمة مرور' }}</option>
                @if($share->password_hash)
                    <option value="remove">إزالة كلمة المرور</option>
                @endif
            </select>
            <div id="newPassWrap" class="d-none">
                <input class="form-control" type="password" name="password" placeholder="كلمة المرور الجديدة (4 أحرف على الأقل)">
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-accent flex-grow-1">حفظ التعديلات</button>
            <a class="btn btn-light" href="{{ route('dashboard') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection

