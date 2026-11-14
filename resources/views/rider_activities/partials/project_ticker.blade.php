@php
  $projectTicker = $projectTicker ?? [];
  $demoMode = $demoMode ?? 'activities';
  $topBarSliderCategories = $topBarSliderCategories ?? collect();
  $topBarOptionStats = $topBarOptionStats ?? [];
  $topBarConfig = $topBarConfig ?? \App\Support\ErpModuleRegistry::topBarConfig('riders');
  $optionIdParam = (string) (($topBarConfig['request']['option_id'] ?? null) ?: 'rider_top_option_id');
  $selectedOptionId = (int) request($optionIdParam, 0);
  $statusBaseParams = request()->except([$optionIdParam, 'page']);
  $projectBaseParams = request()->except(['customer_id', 'page']);
  $palette = [
    ['#3b3fd8', '#eef0ff'],
    ['#0284c7', '#e0f2fe'],
    ['#15803d', '#dcfce7'],
    ['#c2410c', '#ffedd5'],
    ['#be185d', '#fce7f3'],
    ['#7c3aed', '#f5f3ff'],
    ['#0f766e', '#ccfbf1'],
    ['#b45309', '#fef3c7'],
  ];
  $personSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>';
  $bagSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/></svg>';

  $statusCards = [];
  $si = 0;
  foreach ($topBarSliderCategories as $category) {
    foreach (($category->options ?? []) as $option) {
      $stats = $topBarOptionStats[$option->id] ?? [];
      $active = (int) ($stats['active'] ?? 0);
      $inactive = (int) ($stats['inactive'] ?? 0);
      $colors = $palette[$si % count($palette)];
      $statusCards[] = [
        'id' => (int) $option->id,
        'name' => (string) $option->name,
        'active' => $active,
        'inactive' => $inactive,
        'color' => $colors[0],
        'bg' => $colors[1],
        'selected' => $selectedOptionId === (int) $option->id,
      ];
      $si++;
    }
  }

  $hasCards = count($statusCards) > 0 || count($projectTicker) > 0;
@endphp

@if($hasCards)
<div class="ra-tk" id="raTicker" aria-label="Status and project cards">
  <div class="ra-tk-track" id="raTkTrack">
    @for($loopPass = 0; $loopPass < 2; $loopPass++)
      {{-- Rider Settings top-bar status options --}}
      @foreach($statusCards as $c)
        @php
          $params = $statusBaseParams;
          if (!empty($c['selected'])) {
            unset($params[$optionIdParam]);
          } else {
            $params[$optionIdParam] = $c['id'];
          }
          $href = request()->url() . '?' . http_build_query($params);
        @endphp
        <a href="{{ $href }}"
           class="ra-pc{{ !empty($c['selected']) ? ' on' : '' }}"
           data-option-id="{{ $c['id'] }}"
           title="{{ $c['name'] }}"
           @if($loopPass === 1) aria-hidden="true" tabindex="-1" @endif>
          <div class="pn">
            <i style="background:{{ $c['bg'] }};color:{{ $c['color'] }}">{!! $personSvg !!}</i>
            {{ $c['name'] }}
          </div>
          <div class="num">
            <b>{{ number_format($c['active'] + $c['inactive']) }}</b>
            <span>riders</span>
          </div>
          <div class="sub">
            <span class="ps"><i></i> {{ number_format($c['active']) }} active</span>
            <span class="ab"><i></i> {{ number_format($c['inactive']) }} inactive</span>
          </div>
        </a>
      @endforeach

      {{-- Project / customer cards --}}
      @foreach($projectTicker as $p)
        @php
          $selected = !empty($p['selected']);
          $params = $projectBaseParams;
          if ($selected) {
            unset($params['customer_id']);
          } else {
            $params['customer_id'] = $p['id'];
          }
          $href = request()->url() . '?' . http_build_query($params);
        @endphp
        <a href="{{ $href }}" class="ra-pc{{ $selected ? ' on' : '' }}" data-project-id="{{ $p['id'] }}" title="{{ $p['name'] }}" @if($loopPass === 1) aria-hidden="true" tabindex="-1" @endif>
          <div class="pn">
            <i style="background:{{ $p['bg'] }};color:{{ $p['color'] }}">{!! $bagSvg !!}</i>
            {{ $p['name'] }}
          </div>
          <div class="num">
            <b>{{ number_format($p['active'] ?? 0) }}</b>
            <span>active riders</span>
          </div>
          <div class="sub">
            <span class="ps"><i></i> {{ number_format($p['present'] ?? 0) }} present</span>
            <span class="ab"><i></i> {{ number_format($p['absent'] ?? 0) }} absent</span>
          </div>
        </a>
      @endforeach
    @endfor
  </div>
</div>
@endif
