@props(['field', 'toolKey'])

@php
    $id = "f_{$toolKey}_{$field['name']}";
    $name = $field['name'];
    $label = $field['label'];
    $val = $field['value'] ?? '';
    $req = ($field['required'] ?? true) ? 'required' : '';
@endphp

@if($field['type'] === 'checkbox')
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked(!empty($val))>
        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@elseif($field['type'] === 'select')
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
        <select class="form-select" id="{{ $id }}" name="{{ $name }}">
            @foreach($field['options'] as $k => $optLabel)
                <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $optLabel }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@elseif($field['type'] === 'range')
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">
            {{ $label }}: <strong data-range-out="{{ $id }}">{{ $val }}{{ $field['suffix'] ?? '' }}</strong>
        </label>
        <input type="range" class="form-range" id="{{ $id }}" name="{{ $name }}"
               min="{{ $field['min'] }}" max="{{ $field['max'] }}" value="{{ $val }}"
               data-suffix="{{ $field['suffix'] ?? '' }}">
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@elseif($field['type'] === 'textarea')
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
        <textarea class="form-control" id="{{ $id }}" name="{{ $name }}" rows="2"
                  placeholder="{{ $field['placeholder'] ?? '' }}" maxlength="{{ $field['maxlength'] ?? 500 }}" {{ $req }}>{{ $val }}</textarea>
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@elseif($field['type'] === 'color')
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
        <input type="color" class="form-control form-control-color w-100" id="{{ $id }}" name="{{ $name }}" value="{{ $val ?: '#ffffff' }}">
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@else
    <div class="mb-3">
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
        @if(!empty($field['media_time']))
            <div class="input-group">
                <input type="{{ $field['type'] }}" class="form-control" id="{{ $id }}" name="{{ $name }}"
                       value="{{ $val }}" min="{{ $field['min'] ?? '' }}" max="{{ $field['max'] ?? '' }}"
                       step="{{ $field['step'] ?? 'any' }}" {{ $req }}>
                <button type="button" class="btn btn-light" data-media-time="{{ $id }}" title="{{ __('أخذ الوقت الحالي من المشغّل') }}">
                    <i class="bi bi-stopwatch"></i> {{ __('الآن') }}
                </button>
            </div>
        @else
            <input type="{{ $field['type'] }}" class="form-control" id="{{ $id }}" name="{{ $name }}"
                   value="{{ $val }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                   min="{{ $field['min'] ?? '' }}" max="{{ $field['max'] ?? '' }}"
                   step="{{ $field['step'] ?? 'any' }}" maxlength="{{ $field['maxlength'] ?? '' }}" {{ $req }}>
        @endif
        <div class="invalid-feedback d-block" data-field-error="{{ $name }}"></div>
    </div>
@endif
