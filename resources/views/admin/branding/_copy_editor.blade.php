@php
    $markers = [];
    $fallbackTokens = [];
    foreach ($homePersonalizations as $key => $personalization) {
        $markers[$personalization['marker']] = [
            'key' => $key,
            'label' => $personalization['label'],
        ];
        $fallbackTokens[$personalization['marker']] = $personalization['value'];
    }

    $pattern = '/(' . implode('|', array_map(
        static fn (string $marker): string => preg_quote($marker, '/'),
        array_keys($markers)
    )) . ')/';
    $segments = preg_split($pattern, $fieldValue, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$fieldValue];
    $fallbackValue = strtr($fieldValue, $fallbackTokens);
    $editorContent = '';
    foreach ($segments as $segment) {
        if (isset($markers[$segment])) {
            $token = $markers[$segment];
            $editorContent .= '<span class="branding-copy-token" contenteditable="false" data-copy-token="'.
                e($token['key']).'" aria-label="Automatic '.e($token['label']).'">'.e($token['label']).'</span>';
        } else {
            $editorContent .= e($segment);
        }
    }
@endphp

<div class="branding-copy-field" data-copy-field>
<label
    id="{{ $fieldId }}-label"
    for="{{ $fieldId }}"
    class="form-label fw-semibold"
    data-copy-label
    @if (isset($fieldKey)) data-faq-label="{{ $fieldKey }}" @endif
>{{ $fieldLabel }}</label>
<div
    id="{{ $fieldId }}-editor"
    class="form-control branding-copy-editor d-none {{ $fieldMultiline ? 'branding-copy-editor-multiline' : '' }}"
    contenteditable="true"
    hidden
    role="textbox"
    aria-labelledby="{{ $fieldId }}-label"
    aria-multiline="{{ $fieldMultiline ? 'true' : 'false' }}"
    aria-required="{{ $fieldRequired ? 'true' : 'false' }}"
    data-copy-editor
    data-copy-maxlength="{{ $fieldMaxLength }}"
    data-copy-required="{{ $fieldRequired ? 'true' : 'false' }}"
    @if (isset($fieldKey)) data-copy-editor-key="{{ $fieldKey }}" @endif
    spellcheck="true"
>{!! $editorContent !!}</div>
@if ($fieldMultiline)
    <textarea
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        class="form-control"
        rows="2"
        maxlength="{{ $fieldMaxLength }}"
        @if ($fieldRequired) required @endif
        data-copy-fallback
        @if (isset($fieldKey)) data-faq-key="{{ $fieldKey }}" @endif
    >{{ $fallbackValue }}</textarea>
@else
    <input
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        class="form-control"
        maxlength="{{ $fieldMaxLength }}"
        @if ($fieldRequired) required @endif
        value="{{ $fallbackValue }}"
        data-copy-fallback
        @if (isset($fieldKey)) data-faq-key="{{ $fieldKey }}" @endif
    >
@endif
<input
    id="{{ $fieldId }}-value"
    type="hidden"
    value="{{ $fieldValue }}"
    data-copy-value
    @if (isset($fieldKey)) data-faq-key="{{ $fieldKey }}" @endif
>
<div class="text-danger small mt-1" data-copy-error hidden></div>
</div>
