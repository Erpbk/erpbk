@php
  $activeTab = $approvalTab ?? request('approval_tab', 'all');
  $tabCounts = array_merge([
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'cashp' => 0,
  ], $tabCounts ?? []);
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $jvParams = array_filter(['vt' => 'JV', 'company_slug' => $__companySlug]);
@endphp
<div class="vc-tb">
  <div class="l">
    <div class="vc-dd">
      <button type="button" class="vc-btn" id="vcActBtn" aria-haspopup="true" aria-expanded="false">
        Actions ▾
      </button>
      <div class="vc-menu" id="vcActMenu" role="menu">
        @can('vouchers_create')
        <a role="menuitem" class="mi show-modal" href="javascript:void(0);"
           data-size="xl"
           data-title="Create Journal Voucher"
           data-action="{{ route('vouchers.create', $jvParams) }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9 14h6"/></svg>
          <span><b>Create Journal Voucher</b><small>Add a new journal entry voucher</small></span>
        </a>
        @endcan
        <button type="button" role="menuitem" class="mi" id="vcExportBtn" data-phase2="export">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9.5 14.5 12 17l2.5-2.5"/></svg>
          <span><b>Export Vouchers</b><small>Export filtered vouchers (visible columns, current order)</small></span>
        </button>
        @can('vouchers_create')
        <button type="button" role="menuitem" class="mi" id="vcImportBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 17v-6M9.5 13.5 12 11l2.5 2.5"/></svg>
          <span><b>Import Voucher</b><small id="vcImportHint">Add new vouchers from Excel or CSV</small></span>
          <em class="vc-imp-badge" id="vcImportBadge" hidden>Resume</em>
        </button>
        @endcan
        <button type="button" role="menuitem" class="mi openColumnControlSidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
          <span><b>Column Control</b><small>Open column control modal</small></span>
        </button>
      </div>
    </div>

    <div class="vc-tabs" id="vcTabs">
      @foreach([
        ['all', 'All'],
        ['pending', 'Pending'],
        ['approved', 'Approved'],
        ['rejected', 'Rejected'],
        ['cashp', 'Cash Pending'],
      ] as [$key, $label])
      <button type="button"
              data-tab="{{ $key }}"
              class="{{ $key === 'cashp' ? 'cp '.($tabCounts['cashp'] > 0 ? 'has' : '') : '' }} {{ $activeTab === $key ? 'on' : '' }}">
        {{ $label }} <b id="vcTabCount-{{ $key }}">{{ $tabCounts[$key] }}</b>
      </button>
      @endforeach
    </div>
  </div>

  <div class="r">
    <button type="button" class="vc-btn icon" id="vcCopyBtn" title="Copy table to clipboard" aria-label="Copy table">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
    </button>
    <input class="vc-search" id="quickSearch" name="quick_search" type="search" placeholder="Quick Search..." value="{{ request('quick_search') }}" aria-label="Quick search">
    <button type="button" class="vc-btn openFilterSidebar" id="vcFilterBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      Filter
    </button>
  </div>
</div>
