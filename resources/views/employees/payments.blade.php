@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Payment Out Sent')
@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<style>
  .table-responsive { max-height: calc(100vh - 280px); }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@canany(['employees_payment_view', 'employees_payments_view', 'employees_payments_create'])
<div class="riders-list-demo">
  <div class="rl-wrap">
    @include('flash::message')

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="empPayActBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Payment Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="empPayActMenu" hidden role="menu">
            @can('employees_payments_create')
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Employee Payment" data-action="{{ route('payments.create') }}?employee_payment=1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
              <span><strong>Salary Payment</strong><em>Record a payment against employee invoices</em></span>
            </a>
            @endcan
          </div>
        </div>
        <a class="rl-btn" href="{{ route('employee.payment') }}">All Payments</a>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Employee Payments</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          @include('payments.table')
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var btn = document.getElementById('empPayActBtn');
  var menu = document.getElementById('empPayActMenu');
  if (!btn || !menu) return;
  btn.addEventListener('click', function () {
    var open = menu.hidden;
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  document.addEventListener('click', function (e) {
    if (btn.contains(e.target) || menu.contains(e.target)) return;
    menu.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  });
})();
</script>
@endcanany
@endsection
