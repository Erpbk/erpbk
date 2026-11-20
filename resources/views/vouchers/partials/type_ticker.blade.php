@php
  $voucherTypes = $voucherTypesForFilter ?? \App\Helpers\General::VoucherType();
  $selectedType = request('voucher_type');
  $typeStats = $typeTickerStats ?? [];
  $cards = [];
  foreach ($voucherTypes as $code => $label) {
    $cards[] = [
      'code' => $code,
      'label' => $label,
      'a' => (int) ($typeStats[$code]['a'] ?? 0),
      'p' => (int) ($typeStats[$code]['p'] ?? 0),
      'sel' => (string) $selectedType === (string) $code,
    ];
  }
  usort($cards, static fn ($x, $y) => ($y['a'] + $y['p']) <=> ($x['a'] + $x['p']));
  $cardIco = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>';
@endphp
<div class="vc-strip" id="vcStrip" aria-label="Voucher type cards">
  <div class="vc-track" id="vcTrack">
    @forelse($cards as $c)
    <a href="{{ request()->fullUrlWithQuery(['voucher_type' => $c['sel'] ? null : $c['code'], 'page' => null]) }}"
       class="vc-card{{ $c['sel'] ? ' sel' : '' }}"
       data-type="{{ $c['code'] }}"
       title="Filter by {{ $c['label'] }}">
      <h4>{{ $c['label'] }}</h4>
      <small>Voucher Type</small>
      <div class="vc-bx">
        <div class="a">{!! $cardIco !!}<span>APPROVED</span><b>{{ $c['a'] }}</b></div>
        <div class="p">{!! $cardIco !!}<span>PENDING</span><b>{{ $c['p'] }}</b></div>
      </div>
    </a>
    @empty
    <div class="vc-phase-note mb-0">No voucher types available.</div>
    @endforelse
    {{-- Duplicate for seamless loop --}}
    @foreach($cards as $c)
    <a href="{{ request()->fullUrlWithQuery(['voucher_type' => $c['sel'] ? null : $c['code'], 'page' => null]) }}"
       class="vc-card{{ $c['sel'] ? ' sel' : '' }}"
       data-type="{{ $c['code'] }}"
       title="Filter by {{ $c['label'] }}"
       aria-hidden="true"
       tabindex="-1">
      <h4>{{ $c['label'] }}</h4>
      <small>Voucher Type</small>
      <div class="vc-bx">
        <div class="a">{!! $cardIco !!}<span>APPROVED</span><b>{{ $c['a'] }}</b></div>
        <div class="p">{!! $cardIco !!}<span>PENDING</span><b>{{ $c['p'] }}</b></div>
      </div>
    </a>
    @endforeach
  </div>
</div>
