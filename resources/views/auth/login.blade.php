@extends('layouts.app')

@section('title', __('تسجيل الدخول'))
@php $activePage = 'login'; @endphp

@section('content')
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1">{{ __('تسجيل الدخول') }}</h1>
    <p class="text-secondary small mb-4">{{ __('أهلاً بعودتك إلى :name', ['name' => config('app.name')]) }}</p>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">{{ __('كلمة المرور') }}</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-accent w-100 py-2">{{ __('دخول') }}</button>
    </form>
    <p class="text-center small text-secondary mt-3 mb-0">
        {{ __('ليس لديك حساب؟') }} <a href="{{ route('register') }}">{{ __('إنشاء حساب جديد') }}</a>
    </p>
</div>
@endsection
