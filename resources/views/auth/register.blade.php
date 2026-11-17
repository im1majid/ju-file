@extends('layouts.app')

@section('title', __('إنشاء حساب'))
@php $activePage = 'register'; @endphp

@section('content')
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1">{{ __('إنشاء حساب جديد') }}</h1>
    <p class="text-secondary small mb-4">{{ __('احفظ روابط ملفاتك، عدّلها، وتحكم بمدة صلاحيتها.') }}</p>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('register.store') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">{{ __('الاسم') }}</label>
            <input class="form-control" id="name" name="name" value="{{ old('name') }}" minlength="2" maxlength="80" autocomplete="name" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('كلمة المرور') }}</label>
            <input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password_confirmation">{{ __('تأكيد كلمة المرور') }}</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
        </div>
        <button class="btn btn-accent w-100 py-2">{{ __('إنشاء الحساب') }}</button>
    </form>
    <p class="text-center small text-secondary mt-3 mb-0">
        {{ __('لديك حساب؟') }} <a href="{{ route('login') }}">{{ __('تسجيل الدخول') }}</a>
    </p>
</div>
@endsection
