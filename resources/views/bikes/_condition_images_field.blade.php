@php
$label = $label ?? ($field->resolvedLabel() ?? 'Vehicle condition photos');
$required = (bool) ($required ?? false);
$controlRequired = (bool) ($controlRequired ?? $required);
$colClass = $colClass ?? 'col-md-6';
$groupClass = $groupClass ?? '';
$wrapperId = $wrapperId ?? 'assign-field-condition_images';
@endphp
<div class="{{ $colClass }} form-group {{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="condition_images">
    <label for="condition_images">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input
        type="file"
        name="condition_images[]"
        id="condition_images"
        class="form-control"
        multiple
        accept="image/*,.pdf,application/pdf,.heic,.heif"
        @if($controlRequired) required data-assign-required="1" @endif>
    <small class="text-muted d-block mt-1" id="condition_images_hint">
        One file is stored as uploaded. Multiple images are combined into a single PDF.
    </small>
</div>
<script>
    (function() {
        var input = document.getElementById('condition_images');
        var hint = document.getElementById('condition_images_hint');
        if (!input || !hint) {
            return;
        }
        input.addEventListener('change', function() {
            var n = this.files ? this.files.length : 0;
            if (n > 1) {
                hint.textContent = n + ' images selected — they will be saved as one PDF.';
            } else if (n === 1) {
                hint.textContent = '1 file selected — it will be stored as-is.';
            } else {
                hint.textContent = 'One file is stored as uploaded. Multiple images are combined into a single PDF.';
            }
        });
    })();
</script>