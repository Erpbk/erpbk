@props([
    'title',
    'icon' => 'ti ti-info-circle',
    'editUrl' => null,
    'editTitle' => 'Edit',
    'editSize' => 'lg',
])

<div class="card border entity-info-card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="{{ $icon }} ti-sm me-2 entity-info-card-icon"></i>
                <b>{{ $title }}</b>
            </div>
            @if ($editUrl)
                <a class="btn btn-sm edit-btn show-modal"
                   href="javascript:void(0);"
                   data-title="{{ $editTitle }}"
                   data-size="{{ $editSize }}"
                   data-action="{{ $editUrl }}">
                    <i class="ti ti-edit"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{ $slot }}
        </div>
    </div>
</div>
