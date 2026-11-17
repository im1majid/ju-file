@props(['url'])

<div class="share-box">
    <div class="qr" data-qr="{{ $url }}"></div>
    <div class="flex-grow-1 w-100">
        <label class="form-label small text-secondary">{{ __('رابط المشاركة') }}</label>
        <div class="input-group mb-2" dir="ltr">
            <input type="text" class="form-control" value="{{ $url }}" readonly>
            <button class="btn btn-accent" type="button" data-copy="{{ $url }}"><i class="bi bi-clipboard"></i> {{ __('نسخ') }}</button>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light btn-sm" href="{{ $url }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> {{ __('فتح') }}</a>
            <button class="btn btn-light btn-sm" type="button" data-qr-download="{{ $url }}"><i class="bi bi-download"></i> {{ __('تنزيل QR') }}</button>
        </div>
    </div>
</div>
