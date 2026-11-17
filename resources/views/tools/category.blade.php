@extends('layouts.app')

@section('title', __('أدوات :title', ['title' => $cat['title']]))
@php
    $activePage = $category;
    $firstKey = array_key_first($tools);
    $firstTool = $tools[$firstKey];
    $clientConfig = [
        'category'  => $category,
        'maxUpload' => \App\Services\ToolCatalog::MAX_UPLOAD_BYTES,
        'tools'     => array_map(fn ($t) => [
            'title'  => $t['title'],
            'desc'   => $t['desc'],
            'engine' => $t['engine'],
            'accept' => $t['accept'],
            'multi'  => $t['multi'] ?? false,
            'cropUi' => $t['crop_ui'] ?? false,
            'trimUi' => $t['trim_ui'] ?? false,
        ], $tools),
    ];
@endphp

@section('content')
<div class="mb-4">
    <nav class="small text-secondary mb-1">
        <a href="{{ route('home') }}" class="link-secondary text-decoration-none">{{ __('الرئيسية') }}</a> / {{ $cat['title'] }}
    </nav>
    <h1 class="h3 fw-bold mb-1"><i class="bi {{ $cat['icon'] }} text-accent"></i> {{ __('أدوات :title', ['title' => $cat['title']]) }}</h1>
    <p class="text-secondary mb-0">{{ $cat['desc'] }}</p>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <span class="small fw-bold text-secondary"><i class="bi bi-1-circle text-accent"></i> {{ __('اختر الأداة المطلوبة') }}</span>
</div>
<div class="tool-grid mb-4" role="tablist" aria-label="{{ __('أدوات :title', ['title' => $cat['title']]) }}">
    @foreach($tools as $key => $t)
        <button type="button" class="tool-chip {{ $key === $firstKey ? 'active' : '' }}" data-tool="{{ $key }}">
            <i class="bi {{ $t['icon'] }}"></i>
            <span>{{ $t['title'] }}</span>
        </button>
    @endforeach
</div>

<div class="row g-4 align-items-start">
    <div class="col-lg-5">
        <div class="panel">
            <div class="small fw-bold text-accent mb-2"><i class="bi bi-3-circle"></i> {{ __('اضبط الخيارات ونفّذ') }}</div>
            <h2 class="h5 fw-bold mb-1" id="toolTitle">{{ $firstTool['title'] }}</h2>
            <p class="small text-secondary mb-3" id="toolDesc">{{ $firstTool['desc'] }}</p>

            <div class="small text-secondary mb-3" id="engineNote">
                @if($firstTool['engine'] === 'server')
                    <i class="bi bi-hdd-network"></i> {{ __('تُعالج على الخادم — الحد الأقصى :size', ['size' => \App\Services\ToolCatalog::formatBytes(\App\Services\ToolCatalog::MAX_UPLOAD_BYTES)]) }}
                @else
                    <i class="bi bi-shield-check"></i> {{ __('تُعالج مباشرة داخل متصفحك دون رفع الملف للخادم') }}
                @endif
            </div>

            @foreach($tools as $key => $t)
                <form class="tool-form {{ $key === $firstKey ? '' : 'd-none' }}" id="form-{{ $key }}" data-tool="{{ $key }}" enctype="multipart/form-data" novalidate>
                    @if(!empty($t['crop_ui']))
                        <div class="mb-3">
                            <label class="form-label d-block">{{ __('نسبة القص') }}</label>
                            <div class="d-flex flex-wrap gap-1" id="cropRatios">
                                @foreach(['free' => __('حر'), '1:1' => __('1:1 مربع'), '16:9' => '16:9', '9:16' => '9:16', '4:3' => '4:3', '4:5' => '4:5'] as $rk => $rl)
                                    <button type="button" class="btn btn-light btn-sm {{ $rk === 'free' ? 'active' : '' }}" data-crop-ratio="{{ $rk }}">{{ $rl }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="p-2 px-3 rounded-3 bg-light border d-flex justify-content-between align-items-center small mb-3">
                            <span class="text-secondary"><i class="bi bi-bounding-box text-accent"></i> {{ __('التحديد بالماوس') }}</span>
                            <strong id="cropReadout" dir="ltr">{{ __('اسحب على الصورة') }}</strong>
                        </div>
                        <details class="mb-3 small">
                            <summary class="text-secondary" style="cursor:pointer">{{ __('تعديل القياسات يدوياً (اختياري)') }}</summary>
                            <div class="row g-2 mt-1">
                                @foreach($t['fields'] as $field)
                                    <div class="col-6">
                                        <x-tool-field :field="$field" :tool-key="$key" />
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @elseif(!empty($t['trim_ui']))
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                <span class="text-secondary"><i class="bi bi-sliders text-accent"></i> {{ __('اسحب لتحديد المقطع') }}</span>
                                <strong id="trimReadout-{{ $key }}" dir="ltr">{{ __('ارفع ملفاً أولاً') }}</strong>
                            </div>
                            <div class="trim-track" data-trim-track="{{ $key }}" dir="ltr">
                                <div class="trim-range"></div>
                                <span class="trim-handle" data-trim-edge="start" title="{{ __('البداية') }}"></span>
                                <span class="trim-handle" data-trim-edge="end" title="{{ __('النهاية') }}"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button type="button" class="btn btn-light btn-sm" data-trim-preview="{{ $key }}" disabled>
                                    <i class="bi bi-play-circle"></i> {{ __('معاينة المقطع المحدد') }}
                                </button>
                                <span class="small text-secondary" id="trimDur-{{ $key }}"></span>
                            </div>
                        </div>
                        @foreach($t['fields'] as $field)
                            @if($field['type'] === 'checkbox')
                                <x-tool-field :field="$field" :tool-key="$key" />
                            @endif
                        @endforeach
                        <details class="mb-3 small">
                            <summary class="text-secondary" style="cursor:pointer">{{ __('تعديل الثواني يدوياً (اختياري)') }}</summary>
                            <div class="row g-2 mt-1">
                                @foreach($t['fields'] as $field)
                                    @if($field['type'] !== 'checkbox')
                                        <div class="col-6">
                                            <x-tool-field :field="$field" :tool-key="$key" />
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @else
                        @foreach($t['fields'] as $field)
                            <x-tool-field :field="$field" :tool-key="$key" />
                        @endforeach
                    @endif

                    <button type="submit" class="btn btn-accent w-100 py-2" disabled>
                        <i class="bi bi-play-fill"></i> {{ __('تنفيذ الأداة') }}
                    </button>
                </form>
            @endforeach

            <div id="runHint" class="small text-secondary text-center mt-2">
                <i class="bi bi-info-circle"></i> {{ __('ارفع ملفاً أولاً لتفعيل زر التنفيذ') }}
            </div>
            <div id="status" class="mt-3"></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-accent"><i class="bi bi-2-circle"></i> {{ __('ارفع الملف للمعاينة') }}</span>
                <button type="button" id="clearFilesBtn" class="btn btn-light btn-sm d-none">
                    <i class="bi bi-x-lg"></i> {{ __('مسح الملف') }}
                </button>
            </div>
            <label for="fileInput" id="dropzone" class="dropzone">
                <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                <div class="fw-bold mt-2">{{ __('اسحب الملف هنا أو اضغط للاختيار') }}</div>
                <div class="small text-secondary mt-1" id="acceptHint"></div>
                <input id="fileInput" type="file" class="d-none">
            </label>

            <div id="preview" class="mt-3 d-none"></div>
        </div>

        <div id="result" class="panel d-none"></div>
    </div>
</div>

<script>window.JU_PAGE = @json($clientConfig, JSON_UNESCAPED_UNICODE);</script>
@endsection

@push('scripts')
    @foreach($scripts as $s)
        <script src="{{ asset($s) }}"></script>
    @endforeach
@endpush
