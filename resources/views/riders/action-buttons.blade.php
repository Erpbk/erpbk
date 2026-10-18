<!-- Action Buttons Component -->
@php
$companySlug = request()->route('company_slug');
$isRiderEditPage = request()->routeIs('riders.edit');
$riderModel = $rider ?? $riders ?? null;
$riderPendingDeletion = record_is_pending_deletion($riderModel);

// Rider voucher dropdown on profile (Advance Loan excluded). Permissions map per type.
$riderVoucherCodes = ['PAY', 'COD', 'PN', 'INC', 'VC'];
$activeVoucherTypes = \App\Models\VoucherType::activeCodeLabelMapForModule('riders');
$profileVoucherTypes = [];
foreach ($riderVoucherCodes as $code) {
if (isset($activeVoucherTypes[$code])) {
$profileVoucherTypes[$code] = $activeVoucherTypes[$code];
}
}
$voucherPermissionByCode = [
'PAY' => 'riders_payments_create',
'COD' => 'riders_voucher_create',
'PN' => 'riders_voucher_create',
'INC' => 'riders_voucher_create',
'VC' => 'riders_voucher_create',
];
$visibleProfileVoucherTypes = [];
foreach ($profileVoucherTypes as $code => $label) {
$permission = $voucherPermissionByCode[$code] ?? 'riders_voucher_create';
if (user_can($permission)) {
$visibleProfileVoucherTypes[$code] = $label;
}
}
@endphp
@if(!$isRiderEditPage && isset($result))
<div class="card-footer border-top fixed-footer mt-3" style="padding-top: 25px;">
    <div class="d-flex justify-content-start gap-2 flex-wrap align-items-center">
        @if($riderPendingDeletion)
        @include('delete_requests._pending_badge', ['model' => $riderModel])
        <span class="text-muted small">This record is locked pending delete approval.</span>
        @else
        @can('riders_rider_edit')
        <a href="{{ route('riders.edit', ['company_slug' => $companySlug, 'rider' => $result['id']]) }}" class="btn btn-outline-primary waves-effect waves-light">
            <i class="fa fa-edit"></i>&nbsp;Edit Profile
        </a>
        @endcan
        @can('email_create')
        <a href="javascript:void(0);" data-action="{{ route('rider.sendemail', ['company_slug' => $companySlug, 'id' => $result['id']]) }}" data-size="md"
            data-title="{{ $result['name'] . ' (' . $result['rider_id'] . ')' }}"
            class="btn btn-outline-warning show-modal text-nowrap">
            <i class="fas fa-envelope"></i>&nbsp;Send Email
        </a>
        @endcan
        @can('riders_timeline_create')
        <a href="javascript:void(0);" data-action="{{ route('rider.job_status', ['company_slug' => $companySlug, 'id' => $result['id']]) }}" data-size="md" data-title="Add Timeline" class="btn btn-outline-success text-nowrap show-modal">
            <i class="fas fa-chart-bar"></i>&nbsp;Add Timeline
        </a>
        @endcan
        @if(!empty($visibleProfileVoucherTypes))
        <div class="action-dropdown-container rider-profile-voucher-dropdown">
            <button type="button" class="btn btn-outline-secondary text-nowrap" id="riderProfileVoucherBtn">
                <i class="ti ti-file-invoice"></i>&nbsp;Vouchers
                <i class="ti ti-chevron-up ms-1"></i>
            </button>
            <div class="action-dropdown-menu" id="riderProfileVoucherDropdown">
                @foreach($visibleProfileVoucherTypes as $code => $label)
                @php
                $isPaymentVoucher = $code === 'PAY';
                $itemAction = $isPaymentVoucher
                ? route('payments.create', array_filter([
                'invoice_type' => 'rider',
                'rider_id' => $result['id'],
                'company_slug' => $companySlug,
                ]))
                : route('riders.voucher.create', array_filter([
                'type' => $code,
                'rider_id' => $result['id'],
                'company_slug' => $companySlug,
                ]));
                $itemTitle = $isPaymentVoucher ? 'Record Rider Payment' : $label;
                $itemDesc = $isPaymentVoucher
                ? 'Record payment against an unpaid rider invoice'
                : 'Open ' . $label . ' voucher form';
                @endphp
                <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                    data-size="xl"
                    data-title="{{ $itemTitle }}"
                    data-action="{{ $itemAction }}">
                    <i class="ti ti-file-invoice"></i>
                    <div>
                        <div class="action-dropdown-item-text">{{ $label }}</div>
                        <div class="action-dropdown-item-desc">{{ $itemDesc }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
        @endif
    </div>
</div>
<style>
    /* Keep menu above page cards; fixed positioning avoids footer overflow clipping */
    .rider-profile-voucher-dropdown .action-dropdown-menu {
        position: fixed;
        top: auto;
        left: auto;
        right: auto;
        bottom: auto;
        margin: 0;
        z-index: 2000;
        transform: none;
        max-height: min(70vh, 420px);
        overflow-y: auto;
    }

    .rider-profile-voucher-dropdown .action-dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: none;
    }

    .fixed-footer.voucher-dropdown-open {
        overflow: visible;
        z-index: 2000;
    }
</style>
<script>
    (function() {
        function bindRiderProfileVoucherDropdown() {
            if (typeof jQuery === 'undefined') {
                setTimeout(bindRiderProfileVoucherDropdown, 50);
                return;
            }
            var $ = jQuery;
            var $btn = $('#riderProfileVoucherBtn');
            var $menu = $('#riderProfileVoucherDropdown');
            var $container = $btn.closest('.rider-profile-voucher-dropdown');
            var $footer = $btn.closest('.fixed-footer');

            function closeMenu() {
                $menu.removeClass('show').css({
                    top: '',
                    left: '',
                    bottom: '',
                    right: '',
                    position: '',
                    zIndex: ''
                });
                if ($container.length && $menu.parent()[0] !== $container[0]) {
                    $container.append($menu);
                }
                $btn.removeClass('open');
                $footer.removeClass('voucher-dropdown-open');
            }

            function positionMenu() {
                if (!$btn.length || !$menu.length) return;
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
                // Move to body so footer overflow / card stacking cannot hide it
                if ($menu.parent()[0] !== document.body) {
                    $('body').append($menu);
                }
                $footer.addClass('voucher-dropdown-open');
                $menu.addClass('show');
                $btn.addClass('open');
                positionMenu();
            }

            $(document).off('click.riderProfileVoucher', '#riderProfileVoucherBtn')
                .on('click.riderProfileVoucher', '#riderProfileVoucherBtn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if ($menu.hasClass('show')) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });
            $(document).off('click.riderProfileVoucherOutside')
                .on('click.riderProfileVoucherOutside', function(e) {
                    if (!$(e.target).closest('#riderProfileVoucherBtn, #riderProfileVoucherDropdown').length) {
                        closeMenu();
                    }
                });
            $(document).off('click.riderProfileVoucherItem', '#riderProfileVoucherDropdown .show-modal')
                .on('click.riderProfileVoucherItem', '#riderProfileVoucherDropdown .show-modal', function() {
                    closeMenu();
                });
            $(window).off('resize.riderProfileVoucher scroll.riderProfileVoucher')
                .on('resize.riderProfileVoucher scroll.riderProfileVoucher', function() {
                    if ($menu.hasClass('show')) {
                        positionMenu();
                    }
                });
        }
        bindRiderProfileVoucherDropdown();
    })();
</script>
@endif