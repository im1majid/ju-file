@extends('layouts.app')

@section('title', 'الحساب')

@section('content')
<div class="mx-auto" style="max-width:620px">
    <h1 class="h3 fw-bold mb-1">إعدادات الحساب</h1>
    <p class="text-secondary mb-4">{{ $user->email }} · عضو منذ <span dir="ltr">{{ $user->created_at?->format('Y-m-d') }}</span></p>

    <div class="panel mb-4">
        <h2 class="h6 fw-bold mb-3">الملف الشخصي</h2>
        @error('name')
            <div class="alert alert-danger py-2 small">{{ $message }}</div>
        @enderror
        <form method="post" action="{{ route('account.profile') }}" class="d-flex gap-2">
            @csrf
            @method('PUT')
            <input class="form-control" name="name" value="{{ old('name', $user->name) }}" maxlength="80" required aria-label="الاسم">
            <button class="btn btn-accent text-nowrap">حفظ الاسم</button>
        </form>
    </div>

    <div class="panel mb-4">
        <h2 class="h6 fw-bold mb-3">تغيير كلمة المرور</h2>
        @if($errors->has('password') || $errors->has('new_password'))
            <div class="alert alert-danger py-2 small">{{ $errors->first('password') ?: $errors->first('new_password') }}</div>
        @endif
        <form method="post" action="{{ route('account.password') }}">
            @csrf
            @method('PUT')
            <input class="form-control mb-2" type="password" name="current_password" placeholder="كلمة المرور الحالية" autocomplete="current-password" required>
            <input class="form-control mb-2" type="password" name="new_password" placeholder="كلمة المرور الجديدة" autocomplete="new-password" required>
            <input class="form-control mb-3" type="password" name="new_password_confirm" placeholder="تأكيد كلمة المرور الجديدة" autocomplete="new-password" required>
            <button class="btn btn-dark">تحديث كلمة المرور</button>
        </form>
    </div>

    <div class="panel border-danger-subtle">
        <h2 class="h6 fw-bold text-danger mb-2">حذف الحساب</h2>
        <p class="small text-secondary mb-3">سيتم حذف حسابك وجميع الملفات التي شاركتها نهائياً.</p>
        @error('delete')
            <div class="alert alert-danger py-2 small">{{ $message }}</div>
        @enderror
        <form method="post" action="{{ route('account.destroy') }}" class="d-flex gap-2" data-confirm="هل أنت متأكد من حذف حسابك نهائياً؟">
            @csrf
            @method('DELETE')
            <input class="form-control" type="password" name="current_password" placeholder="أدخل كلمة المرور للتأكيد" required>
            <button class="btn btn-danger text-nowrap">حذف الحساب</button>
        </form>
    </div>
</div>
@endsection

