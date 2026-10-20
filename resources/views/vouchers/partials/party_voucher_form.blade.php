@php
/**
 * Variant A — Party / charge voucher form (COD, PN, INC, VC).
 * Expected vars:
 * - $voucherCode (string)
 * - $title, $subtitle (string)
 * - $partyLabel (string) e.g. Rider / Employee
 * - $party (model with ->account, ->name, optional rider_id/employee_id)
 * - $counterMode: 'select'|'locked'
 * - $counterAccounts: id=>name map when select
 * - $counterAccountId, $counterAccountLabel when locked
 * - $defaultNarrationParty, $defaultNarrationCounter
 * - $showReference (bool, default true)
 */
$currency = \App\Helpers\Currency::code();
$voucherCode = $voucherCode ?? 'PN';
$title = $title ?? 'Voucher';
$subtitle = $subtitle ?? '';
$partyLabel = $partyLabel ?? 'Party';
$party = $party ?? ($employee ?? $rider ?? null);
$partyAccount = $party->account ?? null;
$partyName = $party->name ?? '';
$partyCode = $party->rider_id ?? $party->employee_id ?? ($partyAccount->account_code ?? null);
$counterMode = $counterMode ?? 'locked';
$counterAccounts = $counterAccounts ?? [];
$counterAccountId = $counterAccountId ?? null;
$counterAccountLabel = $counterAccountLabel ?? '';
$defaultNarrationParty = $defaultNarrationParty ?? '';
$defaultNarrationCounter = $defaultNarrationCounter ?? '';
$showReference = $showReference ?? true;
$billingMonthDefault = old('billing_month', date('Y-m'));
try {
    $billingMonthDefault = \Carbon\Carbon::parse($billingMonthDefault)->format('Y-m');
} catch (\Throwable $e) {
    $billingMonthDefault = date('Y-m');
}
@endphp

<input type="hidden" name="voucher_type" value="{{ $voucherCode }}" />
@include('vouchers._branch_field')

<div class="uvf-form rider-payment-form"
    data-uvf-party-voucher="1"
    data-voucher-code="{{ $voucherCode }}"
    data-modal-title="{{ $title }}"
    data-modal-subtitle="{{ $subtitle }}">

    <div class="rpf-header" data-rpf-inline-header>
        <h4 class="rpf-title">{{ $title }}</h4>
        @if($subtitle)
        <p class="rpf-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="uvf_trans_date">Date <span class="text-danger">*</span></label>
                    <input type="date" name="trans_date" id="uvf_trans_date" class="form-control rpf-input"
                        value="{{ old('trans_date', date('Y-m-d')) }}" required>
                </div>

                @if($showReference)
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="uvf_reference_number">Reference</label>
                    <input type="text" name="reference_number" id="uvf_reference_number" class="form-control rpf-input"
                        value="{{ old('reference_number', isset($voucher) ? ($voucher->reference_number ?? '') : '') }}"
                        placeholder="Enter reference number" maxlength="255">
                </div>
                @endif

                <div class="form-group col-md-6">
                    <label class="rpf-label">{{ $partyLabel }} <span class="text-danger">*</span></label>
                    <input type="hidden" name="account_id[]" value="{{ $partyAccount->id ?? '' }}" />
                    <div class="rpf-payee-box">
                        <div class="rpf-payee-name">{{ $partyName ?: '-' }}</div>
                        @if($partyCode)
                        <div class="rpf-payee-id">{{ $partyCode }}</div>
                        @endif
                    </div>
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="uvf_billing_month">Billing Month <span class="text-danger">*</span></label>
                    <input type="month" name="billing_month" id="uvf_billing_month" class="form-control rpf-input"
                        value="{{ $billingMonthDefault }}" required>
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="uvf_counter_account">Counter Account <span class="text-danger">*</span></label>
                    @if($counterMode === 'select')
                    <select name="account_id[]" id="uvf_counter_account" class="form-control form-select select2 rpf-input" required>
                        <option value="">Select Account</option>
                        @foreach($counterAccounts as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('account_id.1') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="hidden" name="account_id[]" value="{{ $counterAccountId }}" />
                    <div class="rpf-payee-box">
                        <div class="rpf-payee-name">{{ $counterAccountLabel ?: '-' }}</div>
                    </div>
                    @endif
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="uvf_voucher_amount">Amount <span class="text-danger">*</span></label>
                    <div class="input-group rpf-amount-group">
                        <span class="input-group-text">{{ $currency }}</span>
                        <input type="number" step="any" min="0" name="dr_amount[]" id="uvf_voucher_amount"
                            class="form-control rpf-input main_amount dr_amount" placeholder="0.00" required>
                    </div>
                    {{-- Second amount mirrors primary for legacy store payloads --}}
                    <input type="hidden" name="dr_amount[]" id="uvf_voucher_amount_mirror" class="cr_amount" value="">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rpf-attach-box">
                @include('partials.universal_document_upload', [
                    'name' => 'attachment',
                    'label' => null,
                    'required' => false,
                    'inputClass' => 'form-control',
                    'showHint' => true,
                    'variant' => 'dropzone',
                    'uploadLabel' => 'Upload File',
                ])
                <div class="rpf-attach-hint">Multiple pages combined into one PDF.</div>
            </div>
        </div>
    </div>

    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Narration / Description</h6>
        <div class="form-group mb-0">
            <label class="rpf-label" for="uvf_description">Main Narration / Description <span class="text-danger">*</span></label>
            <textarea name="description" id="uvf_description" class="form-control rpf-input" rows="3"
                placeholder="Enter description or notes about this voucher..." required>{{ old('description', $defaultNarrationParty) }}</textarea>
        </div>
        {{-- Hidden dual narrations for storecod/storepenalty/etc. --}}
        <input type="hidden" name="narration[]" id="uvf_narration_0" value="{{ $defaultNarrationParty }}">
        <input type="hidden" name="narration[]" id="uvf_narration_1" value="{{ $defaultNarrationCounter }}">
    </div>

    <div class="rpf-section mt-4">
        <div class="row g-3">
            <div class="col-lg-7"></div>
            <div class="col-lg-5">
                <div class="rpf-summary">
                    <div class="rpf-summary-row">
                        <span>Amount:</span>
                        <span>{{ $currency }} <span id="uvf_display_amount">0.00</span></span>
                    </div>
                    <div class="rpf-summary-total">
                        Total Voucher: {{ $currency }} <span id="uvf_total_amount">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials.uvf_styles')

<script>
(function() {
    function fmt(n) {
        var v = parseFloat(n);
        if (isNaN(v)) v = 0;
        return v.toFixed(2);
    }

    function syncPartyVoucher() {
        var $root = $('[data-uvf-party-voucher="1"]').last();
        if (!$root.length) return;
        var amount = $root.find('#uvf_voucher_amount').val();
        $root.find('#uvf_voucher_amount_mirror').val(amount);
        $root.find('#uvf_display_amount, #uvf_total_amount').text(fmt(amount));

        var desc = ($root.find('#uvf_description').val() || '').trim();
        var n0 = $root.find('#uvf_narration_0').data('default') || $root.find('#uvf_narration_0').attr('value') || '';
        var n1 = $root.find('#uvf_narration_1').data('default') || $root.find('#uvf_narration_1').attr('value') || '';
        if (!$root.find('#uvf_narration_0').data('default')) {
            $root.find('#uvf_narration_0').data('default', n0);
            $root.find('#uvf_narration_1').data('default', n1);
        }
        if (desc) {
            $root.find('#uvf_narration_0').val(desc);
            $root.find('#uvf_narration_1').val(desc);
        } else {
            $root.find('#uvf_narration_0').val($root.find('#uvf_narration_0').data('default'));
            $root.find('#uvf_narration_1').val($root.find('#uvf_narration_1').data('default'));
        }
    }

    // Expose for onclick handlers that still call getTotal()
    window.getTotal = function() {
        syncPartyVoucher();
    };

    $(document).ready(function() {
        var $root = $('[data-uvf-party-voucher="1"]').last();
        var $counter = $root.find('#uvf_counter_account');
        if ($counter.length && $.fn.select2 && !$counter.hasClass('select2-hidden-accessible')) {
            $counter.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select Account',
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
            });
        }

        $root.off('input.uvfParty change.uvfParty', '#uvf_voucher_amount, #uvf_description')
            .on('input.uvfParty change.uvfParty', '#uvf_voucher_amount, #uvf_description', syncPartyVoucher);

        $('#formajax').off('submit.uvfParty').on('submit.uvfParty', function() {
            syncPartyVoucher();
        });

        syncPartyVoucher();
    });
})();
</script>
