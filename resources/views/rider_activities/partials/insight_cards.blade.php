@php
  $insightCards = collect($insightCards ?? [])
    ->reject(fn ($card) => ! empty($card['hidden_in_demo']))
    ->values()
    ->all();
  $demoMode = $demoMode ?? 'activities';
  $clickable = true;
  $baseParams = request()->except(['insight_card', 'page']);

  $count = count($insightCards);
  $colsClass = $count <= 3 ? 'cols-3' : ($count <= 5 ? 'cols-5' : 'cols-7');

  $icons = [
    'total' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v4H3zM5 7v13h14V7M9 12h6"/></svg>',
    'abs' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    'off2' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>',
    'off4' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>',
    'off6' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>',
    'low_hours' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'low_orders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v4H3zM5 7v13h14V7M9 12h6"/></svg>',
    'pres' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>',
    'labs' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
  ];
@endphp

@if(count($insightCards))
<div class="ra-totals {{ $colsClass }}" id="raInsightCards">
  @foreach($insightCards as $card)
    @php
      $variant = $card['demo_variant'] ?? $card['color'] ?? 'blue';
      $active = !empty($card['active']) && ($card['key'] ?? '') !== 'total';
      $suffix = $card['suffix'] ?? '';
      $key = $card['key'] ?? '';
      $countLabel = is_numeric($card['count'] ?? null)
        ? number_format((float) $card['count'], str_contains((string) ($card['count'] ?? ''), '.') ? 2 : 0)
        : ($card['count'] ?? 0);
      $riderWord = ((int) ($card['count'] ?? 0) === 1) ? 'rider' : 'riders';
      $showRiderWord = $suffix === '' && ! in_array($key, ['avg_ontime', 'total_hours', 'total_orders', 'total_rejected', 'valid_days'], true);
      $icon = $icons[$key] ?? $icons['total'];

      if ($clickable && $key !== '' && ! in_array($key, ['valid_days', 'total_orders', 'avg_ontime', 'total_rejected', 'total_hours'], true)) {
        $params = $baseParams;
        if ($active) {
          unset($params['insight_card']);
        } elseif ($key !== 'total') {
          $params['insight_card'] = $key;
        } else {
          unset($params['insight_card']);
        }
        $href = request()->url() . (count($params) ? '?' . http_build_query($params) : '');
      } else {
        $href = null;
      }
    @endphp
    @if($href)
      <a href="{{ $href }}" class="ra-sc {{ $variant }}{{ $active ? ' on' : '' }}" data-ins="{{ $key }}">
        <div class="sl">{!! $icon !!}{{ $card['title'] ?? '' }}</div>
        <b>{{ $countLabel }}{{ $suffix }}{{ $showRiderWord ? ' ' . $riderWord : '' }}</b>
        <small>{{ $card['meta'] ?? ($card['criteria'] ?? '') }}</small>
      </a>
    @else
      <div class="ra-sc {{ $variant }} static" data-ins="{{ $key }}">
        <div class="sl">{!! $icon !!}{{ $card['title'] ?? '' }}</div>
        <b>{{ $countLabel }}{{ $suffix }}{{ $showRiderWord ? ' ' . $riderWord : '' }}</b>
        <small>{{ $card['meta'] ?? ($card['criteria'] ?? '') }}</small>
      </div>
    @endif
  @endforeach
</div>
@endif
