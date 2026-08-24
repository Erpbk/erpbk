@props([
    'icon' => 'ti ti-info-circle',
    'label',
    'value' => null,
    'html' => false,
    'valueClass' => '',
    'fieldKey' => null,
])

@php
    $display = $value;
    if ($display === null || $display === '') {
        $display = 'not-set';
    }
@endphp
<li class="list-group-item user_list">
    <div class="icons">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="user_list_content">
        <span>{{ $label }}</span>
        <b @class([$valueClass]) @if($fieldKey) data-employee-field="{{ $fieldKey }}" @endif>
            @if($html)
                {!! $display !!}
            @else
                {{ $display }}
            @endif
        </b>
    </div>
</li>
