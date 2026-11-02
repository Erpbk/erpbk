{{-- Profile sticky action bar — HTML demo abar + rider-style voucher dropdown --}}
@php
$companySlug = request()->route('company_slug');
$isEmployeeEditPage = request()->routeIs('employees.edit') || request()->routeIs('employees.create');
$employeeModel = $employee ?? $employees ?? null;
$employeePendingDeletion = record_is_pending_deletion($employeeModel);
$resultId = $result['id'] ?? ($employeeModel->id ?? null);
$resultName = $result['name'] ?? ($employeeModel->name ?? 'Employee');
$resultCode = $result['employee_id'] ?? ($employeeModel->employee_id ?? '');

$voucherTypes = \App\Models\VoucherType::activeCodeLabelMapForModule('employees');
// HTML demo order: Salary Payment, Advance Payment (+ incentive / penalty when enabled)
$profileCodes = [
  'PAY' => 'Salary Payment',
  'AL' => 'Advance Payment',
  'INC' => 'Incentive Voucher',
  'PN' => 'Penalty Voucher',
];
$visibleVouchers = [];
foreach ($profileCodes as $code => $fallbackLabel) {
  if (! isset($voucherTypes[$code])) {
    continue;
  }
  $can = \App\Support\EmployeeVoucherPermissions::can($code, 'create');
  if (! $can && $code === 'PAY') {
    $can = user_can('employees_payments_create') || user_can('employees_voucher_create');
  }
  if ($can) {
    $visibleVouchers[$code] = $voucherTypes[$code] ?: $fallbackLabel;
  }
}
@endphp
@if(!$isEmployeeEditPage && $resultId)
<link rel="stylesheet" href="{{ asset('css/employees-demo.css') }}?v=20261102notes">
<div class="emp-abar emp-demo" id="empActionBar">
  @if($employeePendingDeletion)
    @include('delete_requests._pending_badge', ['model' => $employeeModel])
    <span class="text-muted small">This record is locked pending delete approval.</span>
  @else
    @can('employees_employee_edit')
    <a href="{{ safe_route('employees.edit', ['company_slug' => $companySlug, 'employee' => $resultId], 'employees/{id}/edit') }}" class="emp-abtn e">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
      Edit
    </a>
    @endcan
    @can('email_create')
    <a href="javascript:void(0);"
       data-action="{{ safe_route('employee.sendemail', ['company_slug' => $companySlug, 'id' => $resultId], 'employees/sendemail/{id}') }}"
       data-size="md"
       data-title="{{ $resultName . ' (' . $resultCode . ')' }}"
       class="emp-abtn m show-modal">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 5h20v14H2z" opacity=".25"/><path d="M2 5l10 8 10-8v2l-10 8L2 7z"/></svg>
      Email
    </a>
    @endcan
    @if(!empty($visibleVouchers))
    <div class="emp-vwrap emp-profile-voucher-dropdown">
      <button type="button" class="emp-abtn v" id="empVchBtn" aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
        Vouchers
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 15 6-6 6 6"/></svg>
      </button>
      <div class="emp-vmenu" id="empVchMenu" hidden role="menu">
        @foreach($visibleVouchers as $code => $label)
          @php
            $isPay = $code === 'PAY';
            $isAdvance = $code === 'AL';
            if ($isPay) {
              $itemAction = safe_route('payments.create', array_filter([
                'invoice_type' => 'employee',
                'employee_id' => $resultId,
                'employee_payment' => 1,
                'company_slug' => $companySlug,
              ]), 'payments/create');
              $itemTitle = 'Record Employee Payment';
              $desc = 'Record payment against an unpaid employee invoice';
              $menuLabel = 'Salary Payment';
            } elseif ($isAdvance) {
              $itemAction = safe_route('employees.advanceloan', ['company_slug' => $companySlug, 'id' => $resultId], 'employees/advanceloan/{id}');
              $itemTitle = 'Record Advance Payment';
              $desc = 'Record an advance / loan for this employee';
              $menuLabel = 'Advance Payment';
            } else {
              $itemAction = safe_route('employees.voucher', $resultId, 'employees/{id}/voucher') . '?type=' . $code;
              $itemTitle = $label;
              $desc = match ($code) {
                'INC' => 'Record an incentive entry for this employee',
                'PN' => 'Record a penalty entry for this employee',
                default => 'Open ' . $label,
              };
              $menuLabel = $label;
            }
          @endphp
          <a role="menuitem" href="javascript:void(0);"
             class="show-modal"
             data-action="{{ $itemAction }}"
             data-size="xl"
             data-title="{{ $itemTitle }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
            <span><strong>{{ $menuLabel }}</strong><em>{{ $desc }}</em></span>
          </a>
        @endforeach
      </div>
    </div>
    @endif
  @endif
</div>
<style>
  .emp-abar.voucher-dropdown-open { overflow: visible; z-index: 2000; }
  .emp-profile-voucher-dropdown .emp-vmenu {
    position: fixed;
    top: auto; left: auto; right: auto; bottom: auto;
    margin: 0; z-index: 5000;
    display: none;
    width: 280px;
  }
  .emp-profile-voucher-dropdown .emp-vmenu.show {
    display: block !important;
  }
</style>
<script>
(function () {
  function bindEmpVoucherDropdown() {
    if (typeof jQuery === 'undefined') {
      setTimeout(bindEmpVoucherDropdown, 50);
      return;
    }
    var $ = jQuery;
    var $btn = $('#empVchBtn');
    var $menu = $('#empVchMenu');
    var $wrap = $btn.closest('.emp-profile-voucher-dropdown');
    var $bar = $btn.closest('.emp-abar');
    if (!$btn.length || !$menu.length) return;

    function closeMenu() {
      $menu.removeClass('show').attr('hidden', true).css({
        top: '', left: '', bottom: '', right: '', position: '', zIndex: ''
      });
      if ($wrap.length && $menu.parent()[0] !== $wrap[0]) {
        $wrap.append($menu);
      }
      $btn.attr('aria-expanded', 'false');
      $bar.removeClass('voucher-dropdown-open');
    }

    function positionMenu() {
      var rect = $btn[0].getBoundingClientRect();
      var gap = 8;
      var menuWidth = Math.max($menu.outerWidth() || 280, 280);
      var left = rect.left;
      if (left + menuWidth > window.innerWidth - 12) {
        left = Math.max(12, rect.right - menuWidth);
      }
      $menu.css({
        position: 'fixed',
        top: 'auto',
        bottom: (window.innerHeight - rect.top + gap) + 'px',
        left: left + 'px',
        right: 'auto',
        zIndex: 5000,
        minWidth: Math.max(rect.width, 280) + 'px'
      });
    }

    function openMenu() {
      if ($menu.parent()[0] !== document.body) {
        $('body').append($menu);
      }
      $menu.removeAttr('hidden').addClass('show');
      $btn.attr('aria-expanded', 'true');
      $bar.addClass('voucher-dropdown-open');
      positionMenu();
    }

    $(document).off('click.empProfileVoucher', '#empVchBtn')
      .on('click.empProfileVoucher', '#empVchBtn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ($menu.hasClass('show')) closeMenu();
        else openMenu();
      });
    $(document).off('click.empProfileVoucherOutside')
      .on('click.empProfileVoucherOutside', function (e) {
        if (!$(e.target).closest('#empVchBtn, #empVchMenu').length) closeMenu();
      });
    $(document).off('click.empProfileVoucherItem', '#empVchMenu .show-modal')
      .on('click.empProfileVoucherItem', '#empVchMenu .show-modal', function () {
        closeMenu();
      });
    $(window).off('resize.empProfileVoucher scroll.empProfileVoucher')
      .on('resize.empProfileVoucher scroll.empProfileVoucher', function () {
        if ($menu.hasClass('show')) positionMenu();
      });
  }
  bindEmpVoucherDropdown();
})();
</script>
@endif
