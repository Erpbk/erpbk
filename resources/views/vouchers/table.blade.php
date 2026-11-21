@php
$vfShow = static fn (string $field): bool => field_visible('voucher', $field);
$vfCols = [
  'trans_date' => $vfShow('trans_date'),
  'billing_month' => $vfShow('billing_month'),
  'reference_number' => $vfShow('reference_number'),
  'amount' => $vfShow('amount'),
  'created_by' => $vfShow('created_by'),
  'updated_by' => $vfShow('updated_by'),
  'attach_file' => $vfShow('attach_file'),
];
// Always-shown UI columns (demo): module, account kind, paid from — Phase 1 placeholders where needed.
$vfColspan = count(array_filter($vfCols)) + 7; // + ck, voucher_id, module, type, paid_from, status, actions

$moduleByType = [
  'PV' => 'Cash & Banks',
  'RV' => 'Cash & Banks',
  'TRF' => 'Cash & Banks',
  'JV' => 'Vouchers',
  'VL' => 'Visa Expense',
  'LV' => 'Visa Expense',
  'VR' => 'Visa Expense',
  'RFV' => 'RTA Fines',
  'EXP' => 'Expenses',
  'AL' => 'Riders',
  'PN' => 'Riders',
  'COD' => 'Riders',
  'INC' => 'Riders',
  'VC' => 'Riders',
];

$dh = '<svg class="vc-dh" viewBox="0 0 20 18" width="11" height="10"><path d="M4 2h5.5a7 7 0 0 1 0 14H4z" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M1 7h14M1 11h14" stroke="currentColor" stroke-width="1.8"/></svg>';
@endphp
<table class="table list dataTable no-footer" id="dataTableBuilder">
  <thead>
    <tr role="row">
      <th class="nosort vc-ck-col" data-k="ck" data-c="ck">
        <input type="checkbox" class="vc-ck" id="vcCkAll" aria-label="Select all on page">
      </th>
      @if($vfCols['trans_date'])<th class="s sorting" data-k="trans_date" data-c="d">Date<span class="ar">⇅</span></th>@endif
      @if($vfCols['billing_month'])<th class="s sorting" data-k="billing_month" data-c="bm">Billing Month<span class="ar">⇅</span></th>@endif
      <th class="s sorting" data-k="voucher_id" data-c="vno">Voucher ID<span class="ar">⇅</span></th>
      @if($vfCols['reference_number'])<th class="s sorting" data-k="reference_number" data-c="ref">Reference<span class="ar">⇅</span></th>@endif
      <th class="s sorting" data-k="module" data-c="mod">Module<span class="ar">⇅</span></th>
      <th class="s sorting" data-k="account_kind" data-c="k">Type<span class="ar">⇅</span></th>
      <th class="s sorting" data-k="paid_from" data-c="pf">Paid From<span class="ar">⇅</span></th>
      @if($vfCols['amount'])<th class="s sorting" data-k="amount" data-c="amt">Amount<span class="ar">⇅</span></th>@endif
      @if($vfCols['created_by'])<th class="s sorting" data-k="created_by" data-c="cb">Created By<span class="ar">⇅</span></th>@endif
      @if($vfCols['updated_by'])<th class="s sorting" data-k="updated_by" data-c="ub">Updated By<span class="ar">⇅</span></th>@endif
      @if($vfCols['attach_file'])<th class="nosort" data-k="attach_file" data-c="file">File</th>@endif
      <th class="s sorting" data-k="approval_status" data-c="status">Status<span class="ar">⇅</span></th>
      <th class="nosort" data-k="action">Actions</th>
      <th class="sorting_disabled" data-k="search" rowspan="1" colspan="1" aria-hidden="true" style="display:none;"></th>
      <th class="sorting_disabled" data-k="control" rowspan="1" colspan="1" aria-hidden="true" style="display:none;"></th>
    </tr>
  </thead>
  <tbody>
    @php
    $__companySlug = \App\Support\CompanyRouteContext::slug();
    $editDeleteFlags = $editDeleteFlags ?? [];
    $voucherTypes = \App\Helpers\General::VoucherType();
    $voucherRouteParams = static function ($voucherKey) use ($__companySlug): array {
      $params = ['voucher' => $voucherKey];
      if (!empty($__companySlug)) {
        $params['company_slug'] = $__companySlug;
      }
      return $params;
    };
    $listSidebarParams = !empty($__companySlug) ? ['company_slug' => $__companySlug] : [];
    $pageTotal = 0.0;
    $pagePending = 0;
    $pageCash = 0.0;
    $pageBank = 0.0;
    @endphp
    @if(isset($data) && $data->count() > 0)
    @foreach($data as $voucher)
    @php
      $voucherPendingDeletion = record_is_pending_deletion($voucher);
      $moduleActions = \App\Support\VoucherModuleActions::forVoucher($voucher, $__companySlug);
      $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
      $statusRaw = $voucher->approval_status ?? 'pending_approval';
      $statusKey = $statusRaw === 'pending_approval' ? 'pending' : $statusRaw;
      $isPending = in_array($statusKey, ['pending', 'pending_approval'], true);
      if ($isPending) { $pagePending++; }
      $rowAmount = (float) ($voucher->amount ?? 0);
      $pageTotal += $rowAmount;
      $accountKind = $voucher->list_account_kind ?? 'oth';
      if ($accountKind === 'cash') {
        $pageCash += $rowAmount;
      } elseif ($accountKind === 'bank') {
        $pageBank += $rowAmount;
      }
      $moduleLabel = $moduleByType[$voucher->voucher_type] ?? 'Vouchers';
      $typeLabel = $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type;

      $riderVoucherUploadCode = \App\Support\RiderVoucherPermissions::codeFromVoucherType($voucher->voucher_type);
      $canUploadThisVoucherDoc = $riderVoucherUploadCode !== null
        ? \App\Support\RiderVoucherPermissions::canUpload($riderVoucherUploadCode)
        : \App\Support\VoucherModulePermissions::canUpload();

      // Build row-menu HTML for the demo ⋯ menu (escaped for data attribute)
      $menuItems = '';
      if ($canUploadThisVoucherDoc && !$voucherPendingDeletion) {
        $uploadUrl = route('voucher.fileupload', array_filter(['id' => $voucher->id, 'company_slug' => $__companySlug]));
        $menuItems .= '<a class="mi show-modal" href="javascript:void(0);" data-size="sm" data-title="Upload Document" data-action="'.e($uploadUrl).'"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 17v-6M9.5 13.5 12 11l2.5 2.5"/></svg><span><b>Upload Document</b><small>Attach a file to this voucher</small></span></a>';
      }
      if (user_can('voucher_view')) {
        $showUrl = route('vouchers.show', $voucherRouteParams($voucher->id));
        $menuItems .= '<a class="mi show-voucher-panel" href="javascript:void(0);" data-action="'.e($showUrl).'" data-title="'.e($typeLabel.' #'.$voucherId).'" data-collapse-sidebar="1" data-list-url="'.e(route('vouchers.list-sidebar', $listSidebarParams)).'"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg><span><b>View</b><small>Open the full voucher details</small></span></a>';
      }
      if (!$voucherPendingDeletion && (user_can('voucher_edit') || user_can('vouchers_edit')) && !empty($editDeleteFlags[$voucher->voucher_type]['can_edit'])) {
        $menuItems .= '<a class="mi show-modal" href="javascript:void(0);" data-size="'.e($moduleActions['edit_size']).'" data-title="'.e($moduleActions['edit_title']).'" data-action="'.e($moduleActions['edit_url']).'"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg><span><b>Edit</b><small>Change this voucher\'s details</small></span></a>';
      }
      if (!$voucherPendingDeletion && (user_can('voucher_delete') || user_can('vouchers_delete')) && !empty($editDeleteFlags[$voucher->voucher_type]['can_delete'])) {
        $menuItems .= '<button type="button" class="mi del" onclick=\'confirmDelete('.json_encode($moduleActions['delete_url']).')\'><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg><span><b>Delete</b><small>Remove this voucher</small></span></button>';
      }
    @endphp
    <tr class="{{ $isPending ? 'pend' : '' }} {{ $voucherPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $voucher->id }}">
      <td class="vc-ck-col">
        <input type="checkbox" class="vc-ck vc-rck" aria-label="Select {{ $voucherId }}">
      </td>
      @if($vfCols['trans_date'])
      <td>{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</td>
      @endif
      @if($vfCols['billing_month'])
      <td>{{ \App\Helpers\Common::MonthFormat($voucher->billing_month) }}</td>
      @endif
      <td>
        <a href="javascript:void(0);"
           class="vc-lnk show-voucher-panel"
           data-action="{{ route('vouchers.show', $voucherRouteParams($voucher->id)) }}"
           data-title="{{ $typeLabel }} #{{ $voucherId }}"
           data-collapse-sidebar="1"
           data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">{{ $voucherId }}</a>
      </td>
      @if($vfCols['reference_number'])
      <td>{{ $voucher->reference_number ?? '—' }}</td>
      @endif
      <td>{{ $moduleLabel }}</td>
      @php $paidFromRows = $voucher->list_paid_from ?? []; @endphp
      <td>
        <span class="vc-tg {{ $accountKind }}">{{ \App\Support\VoucherAccountKind::labelForKind($accountKind) }}</span>
      </td>
      <td class="pf">
        @if(!empty($paidFromRows))
          @foreach($paidFromRows as $pf)
            <a class="vc-lnk" href="{{ route('accounts.ledger', array_filter(['account' => $pf['id'], 'company_slug' => $__companySlug])) }}" target="_blank" rel="noopener">{{ $pf['label'] }}</a>@if(!$loop->last)<br>@endif
          @endforeach
        @else
          <span style="color:#94a3b8">—</span>
        @endif
      </td>
      @if($vfCols['amount'])
      <td class="vc-amt">{!! $dh !!}{{ number_format((float) $voucher->amount, 2) }}</td>
      @endif
      @if($vfCols['created_by'])
      <td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>
      @endif
      @if($vfCols['updated_by'])
      <td>{{ \App\Helpers\Common::UserName($voucher->Updated_By) }}</td>
      @endif
      @if($vfCols['attach_file'])
      <td class="vc-file-cell">
        @if($voucher->attach_file)
          @php
            $attachRel = ltrim((string) $voucher->attach_file, '/');
            $storageRel = in_array($voucher->voucher_type, ['RFV', 'LV', 'VR'], true)
              ? $attachRel
              : (str_contains($attachRel, '/') ? $attachRel : 'vouchers/'.$attachRel);
            $fileUrl = url('storage/'.$storageRel);
            $fileName = basename($attachRel);
            $diskPath = storage_path('app/public/'.$storageRel);
            $sizeLabel = null;
            if (is_file($diskPath)) {
              $bytes = (int) filesize($diskPath);
              $sizeLabel = $bytes >= 1048576
                ? round($bytes / 1048576, 1).' MB'
                : max(1, (int) round($bytes / 1024)).' KB';
            }
            $uploadedAt = $voucher->updated_at ?? $voucher->created_at ?? null;
            $uploadedLabel = $uploadedAt
              ? 'uploaded '.\Carbon\Carbon::parse($uploadedAt)->format('d M Y')
              : null;
            $fileMetaParts = array_values(array_filter([
              $typeLabel,
              $voucherId,
              $sizeLabel,
              $uploadedLabel,
            ]));
            $fileMeta = implode(' · ', $fileMetaParts);
          @endphp
          <a href="{{ $fileUrl }}"
             class="vc-fbtn js-vc-doc-view"
             rel="noopener"
             data-file-url="{{ $fileUrl }}"
             data-file-name="{{ $fileName }}"
             data-file-meta="{{ $fileMeta }}"
             data-file-context="{{ $typeLabel }}"
             title="{{ $fileName }}"><span class="vc-fico" aria-hidden="true">📄</span> View</a>
        @else
          <span style="color:#94a3b8">-</span>
        @endif
      </td>
      @endif
      <td>
        @if($voucherPendingDeletion)
          @include('delete_requests._pending_badge', ['model' => $voucher])
        @else
          <span class="vc-pill {{ $statusKey }}">{{ ucfirst(str_replace('_', ' ', $statusKey)) }}</span>
        @endif
      </td>
      <td>
        @if($voucherPendingDeletion)
          @include('delete_requests._locked_cell', ['model' => $voucher])
        @else
          <button type="button" class="vc-dots" data-menu-html="{{ $menuItems }}" aria-label="Actions">⋯</button>
        @endif
      </td>
      <td style="display:none;"></td>
      <td style="display:none;"></td>
    </tr>
    @endforeach
    @else
    <tr class="vc-empty empty">
      <td colspan="{{ $vfColspan + 2 }}" class="text-center">
        No vouchers match these filters.
      </td>
    </tr>
    @endif
  </tbody>
  @if(isset($data) && $data->count() > 0)
  @php
    $footSummary = 'Total of <b>'.$data->count().'</b> vouchers'
      .($pagePending ? ' · <span class="p">'.$pagePending.' pending</span>' : '')
      .' <span class="s">Cash '.$dh.number_format($pageCash, 2).' · Bank '.$dh.number_format($pageBank, 2).'</span>';
    // One <td> per header column so Amount total stays aligned (and column-control can sync tfoot).
    $footCells = [];
    // Keep checkbox column empty so footer summary cannot stretch it.
    $footCells[] = ['k' => 'ck', 'class' => 'vc-ck-col', 'html' => ''];
    $summaryPlaced = false;
    $placeSummary = static function (string $key) use (&$footCells, &$summaryPlaced, $footSummary): void {
      if ($summaryPlaced) {
        $footCells[] = ['k' => $key, 'class' => '', 'html' => ''];
        return;
      }
      $footCells[] = ['k' => $key, 'class' => 'l', 'html' => $footSummary];
      $summaryPlaced = true;
    };
    if ($vfCols['trans_date']) { $placeSummary('trans_date'); }
    if ($vfCols['billing_month']) { $placeSummary('billing_month'); }
    $placeSummary('voucher_id');
    if ($vfCols['reference_number']) { $placeSummary('reference_number'); }
    $placeSummary('module');
    $placeSummary('account_kind');
    $placeSummary('paid_from');
    if ($vfCols['amount']) {
      $footCells[] = ['k' => 'amount', 'class' => 'a', 'html' => $dh.number_format($pageTotal, 2)];
    }
    if ($vfCols['created_by']) { $footCells[] = ['k' => 'created_by', 'class' => '', 'html' => '']; }
    if ($vfCols['updated_by']) { $footCells[] = ['k' => 'updated_by', 'class' => '', 'html' => '']; }
    if ($vfCols['attach_file']) { $footCells[] = ['k' => 'attach_file', 'class' => '', 'html' => '']; }
    $footCells[] = ['k' => 'approval_status', 'class' => '', 'html' => ''];
    $footCells[] = ['k' => 'action', 'class' => '', 'html' => ''];
    $footCells[] = ['k' => 'search', 'class' => '', 'html' => '', 'hidden' => true];
    $footCells[] = ['k' => 'control', 'class' => '', 'html' => '', 'hidden' => true];
  @endphp
  <tfoot>
    <tr>
      @foreach($footCells as $fc)
      <td class="{{ $fc['class'] }}"
          data-k="{{ $fc['k'] }}"
          @if(!empty($fc['hidden'])) style="display:none;" aria-hidden="true" @endif>
        {!! $fc['html'] !!}
      </td>
      @endforeach
    </tr>
  </tfoot>
  @endif
</table>

@include('delete_requests._confirm_delete_script', [
  'entityName' => 'Voucher',
  'confirmText' => 'Submit a delete request for this voucher? It will stay in the list as Pending Deletion until an administrator approves.',
])
@include('delete_requests._pending_table_script', ['items' => $data])
<script>
(function runWhenJQueryReady() {
  var $ = window.jQuery || window.$;
  if (typeof $ === 'undefined') {
    setTimeout(runWhenJQueryReady, 50);
    return;
  }
  $(function () {
    var attempts = 0;
    function tryInitialize() {
      attempts++;
      if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        document.querySelectorAll('#dataTableBuilder [data-bs-toggle="dropdown"]').forEach(function (el) {
          try { new bootstrap.Dropdown(el); } catch (e) {}
        });
      } else if (attempts < 10) {
        setTimeout(tryInitialize, 100);
      }
    }
    setTimeout(tryInitialize, 100);
    if (typeof window.vcSyncFooterCols === 'function') window.vcSyncFooterCols();
  });
})();

/** Keep footer summary in the first visible cell before Amount (no colspan drift). */
window.vcSyncFooterCols = function () {
  var table = document.getElementById('dataTableBuilder');
  if (!table) return;
  var foot = table.querySelector('tfoot tr');
  if (!foot) return;
  var cells = Array.from(foot.children);
  var amtIdx = cells.findIndex(function (td) { return td.getAttribute('data-k') === 'amount'; });
  if (amtIdx < 1) return;

  var summaryHtml = '';
  cells.forEach(function (td, i) {
    if (i >= amtIdx) return;
    if (td.getAttribute('data-vc-summary') === '1' && td.innerHTML) summaryHtml = td.innerHTML;
  });
  if (!summaryHtml) {
    for (var i = 0; i < amtIdx; i++) {
      if (cells[i].innerHTML) { summaryHtml = cells[i].innerHTML; break; }
    }
  }

  var placed = false;
  cells.forEach(function (td, i) {
    if (i >= amtIdx) return;
    td.removeAttribute('colspan');
    if (td.classList.contains('column-hidden')) {
      td.innerHTML = '';
      td.classList.remove('l');
      td.removeAttribute('data-vc-summary');
      return;
    }
    // Never put the summary in the checkbox column (keeps it narrow).
    if (td.getAttribute('data-k') === 'ck' || td.classList.contains('vc-ck-col')) {
      td.innerHTML = '';
      td.classList.remove('l');
      td.removeAttribute('data-vc-summary');
      return;
    }
    if (!placed) {
      td.classList.add('l');
      td.setAttribute('data-vc-summary', '1');
      td.innerHTML = summaryHtml;
      placed = true;
    } else {
      td.innerHTML = '';
      td.classList.remove('l');
      td.removeAttribute('data-vc-summary');
    }
  });
};
</script>
