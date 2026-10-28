@php
/** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator $paginator */
$paginator = $paginator ?? $data ?? null;
if (!$paginator || !method_exists($paginator, 'total')) {
    return;
}
$total = (int) $paginator->total();
$perPage = (int) $paginator->perPage();
$current = (int) $paginator->currentPage();
$last = max(1, (int) $paginator->lastPage());
$from = $total === 0 ? 0 : (($current - 1) * $perPage) + 1;
$to = $total === 0 ? 0 : min($current * $perPage, $total);
$query = request()->query();
$appended = $paginator->appends($query);
$nums = [];
for ($p = 1; $p <= $last; $p++) {
    if ($p === 1 || $p === $last || abs($p - $current) <= 1) {
        $nums[] = $p;
    } elseif (end($nums) !== '…') {
        $nums[] = '…';
    }
}
$perPageOptions = $perPageOptions ?? [20, 50, 100, 'all'];
$currentPerPage = request()->input('per_page', $perPage);
$entityLabel = $entityLabel ?? 'riders';
@endphp
<div class="rl-pager" id="rlPager">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="info" id="rlPagerInfo">
      @if($total > 0)
        Showing {{ $from }}–{{ $to }} of {{ $total }} {{ $entityLabel }}
      @else
        0 {{ $entityLabel }}
      @endif
    </span>
    <select class="rl-per-page" id="rlPerPage" aria-label="Rows per page">
      @foreach($perPageOptions as $option)
        <option value="{{ $option }}" @selected((string) $currentPerPage === (string) $option || ($option === 'all' && in_array($currentPerPage, ['all', -1, '-1'], true)))>
          {{ $option === 'all' || $option === -1 ? 'All' : $option }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="rl-pages" id="rlPages">
    @if($current <= 1)
      <span class="rl-pg disabled">‹ Previous</span>
    @else
      <a class="rl-pg" href="{{ $appended->previousPageUrl() }}" data-page="{{ $current - 1 }}">‹ Previous</a>
    @endif
    @foreach($nums as $n)
      @if($n === '…')
        <span class="rl-pg disabled">…</span>
      @elseif($n === $current)
        <span class="rl-pg cur" aria-current="page">{{ $n }}</span>
      @else
        <a class="rl-pg" href="{{ $appended->url($n) }}" data-page="{{ $n }}">{{ $n }}</a>
      @endif
    @endforeach
    @if($current >= $last)
      <span class="rl-pg disabled">Next ›</span>
    @else
      <a class="rl-pg" href="{{ $appended->nextPageUrl() }}" data-page="{{ $current + 1 }}">Next ›</a>
    @endif
  </div>
</div>
