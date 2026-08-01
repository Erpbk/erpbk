@once
<style id="bike-road-status-styles">
    .road-status-badge {
        display: inline-block;
        padding: 4px 16px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
        min-width: 120px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }
    .road-onroad {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: 1px solid #218838;
    }
    .road-offroad {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        border: 1px solid #c82333;
    }
    .road-onroadRed {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: 2px solid #b02a37;
        color: #ffffff;
    }
    .road-returned {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: 1px solid #5c636a;
    }
    .road-absconded {
        background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
        border: 1px solid #842029;
    }
    .road-theft {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        border: 1px solid #4c2b8a;
    }
    .road-total-loss {
        background: linear-gradient(135deg, #343a40 0%, #212529 100%);
        border: 1px solid #1a1d20;
    }
    .road-impound {
        background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
        border: 1px solid #d9480f;
    }
    .road-accident {
        background: linear-gradient(135deg, #b02a37 0%, #922b21 100%);
        border: 1px solid #7b241c;
    }
    .road-status-container .status-days,
    .status-days {
        display: block;
        margin-top: 4px;
        font-size: 0.75rem;
    }
</style>
@endonce
@php
    /** @var \App\Models\Bikes $bike */
    $status = $status ?? $bike->road_status;
@endphp
<div class="d-flex flex-column align-items-center gap-1">
    <span class="road-status-badge {{ $status['class'] }}" title="{{ $status['title'] }}">{{ $status['label'] }}</span>
    @if($status['days'] !== null)
    <small class="text-muted status-days" title="Days since status change">{{ $status['days'] }} {{ $status['days'] === 1 ? 'day' : 'days' }}</small>
    @endif
</div>
