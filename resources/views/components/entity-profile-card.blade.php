@props([
    'icon' => 'ti ti-building',
    'isActive' => true,
    'statusLabel' => 'Active',
    'statusClass' => '',
    'name' => 'not-set',
    'subtitle' => null,
    'photo' => null,
    'editUrl' => null,
    'editTitle' => 'Edit',
    'editSize' => 'lg',
    'nameId' => null,
    'statusId' => null,
])

<div class="card entity-view-card mb-6">
    <div class="user-avatar-section">
        <div class="entity-view-card-hero">
            @if($editUrl)
                <a href="javascript:void(0);"
                   class="entity-view-card-edit show-modal"
                   data-action="{{ $editUrl }}"
                   data-title="{{ $editTitle }}"
                   data-size="{{ $editSize }}"
                   title="{{ $editTitle }}">
                    <i class="ti ti-pencil"></i>
                </a>
            @endif
            {{ $heroStart ?? '' }}
            <div class="entity-view-card-status">
                <span @if($statusId) id="{{ $statusId }}" @endif class="entity-view-card-active {{ $isActive ? '' : 'is-inactive' }} {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <div class="entity-view-card-photo-wrap">
                @if($photo)
                    <img src="{{ $photo }}" id="output" class="entity-view-card-photo" alt="{{ $name }}" />
                    {{ $photoAction ?? '' }}
                @else
                    <div class="entity-view-card-photo-icon" aria-hidden="true">
                        <i class="{{ $icon }}"></i>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="user-info text-center mb-3">
                <h6 class="mb-0"><b @if($nameId) id="{{ $nameId }}" @endif>{{ $name ?: 'not-set' }}</b></h6>
                @if($subtitle)
                    <div class="entity-view-card-id">{{ $subtitle }}</div>
                @endif
                {{ $meta ?? '' }}
            </div>
            {{ $afterHero ?? '' }}
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="info-container">
            <ul class="p-0 mb-3 entity-view-card-list">
                {{ $slot }}
            </ul>
            {{ $footer ?? '' }}
        </div>
    </div>
</div>
