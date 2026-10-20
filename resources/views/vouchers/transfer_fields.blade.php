@php
    $transferLines = collect($data ?? []);
    $chargesAccountId = 0;
    try {
        $chargesAccountId = (int) \App\Support\GlobalAccounts::account('BANK_CHARGES')->id;
    } catch (\Throwable $e) {
        $chargesAccountId = 0;
    }
    $chargeLine = $transferLines->first(function ($line) use ($chargesAccountId) {
        $narration = (string) ($line->narration ?? '');
        $isChargeLine = str_starts_with($narration, 'Bank charges for')
            || ($chargesAccountId > 0 && (int) $line->account_id === $chargesAccountId);
        return $isChargeLine && (float) ($line->debit ?? 0) > 0;
    });
    $fromLine = $transferLines
        ->filter(fn ($line) => (float) ($line->credit ?? 0) > 0)
        ->sortByDesc(fn ($line) => (float) $line->credit)
        ->first();
    $toLine = $transferLines
        ->filter(function ($line) use ($chargeLine) {
            if ((float) ($line->debit ?? 0) <= 0) {
                return false;
            }
            if ($chargeLine && (int) $line->id === (int) ($chargeLine->id ?? 0) && ! empty($line->id)) {
                return false;
            }
            if ($chargeLine && (int) $line->account_id === (int) $chargeLine->account_id) {
                return false;
            }
            return true;
        })
        ->sortByDesc(fn ($line) => (float) $line->debit)
        ->first();

    $savedCharges = round((float) ($chargeLine->debit ?? 0), 2);
    $savedTransfer = $toLine
        ? round((float) $toLine->debit, 2)
        : ($fromLine ? round(max(0, (float) $fromLine->credit - $savedCharges), 2) : null);

    $savedFromAccount = isset($vouchers) ? ($vouchers->payment_from ?? null) : null;
    $savedToAccount = isset($vouchers) ? ($vouchers->payment_to ?? null) : null;
    $fromAccountId = old('from_account_id', $savedFromAccount ?? ($fromLine->account_id ?? ''));
    $toAccountId = old('to_account_id', $savedToAccount ?? ($toLine->account_id ?? ''));
    $transferAmountValue = old('amount', $savedTransfer);
    $bankChargesValue = old('bank_charges', $savedCharges > 0 ? number_format($savedCharges, 2, '.', '') : null);

    $rawNarration = (string) ($fromLine->narration ?? '');
    $descriptionValue = old('description', preg_replace('/\s*\|\s*Bank charges\s+[0-9,]+\.\d{2}\s*$/', '', $rawNarration));

    $billingMonthRaw = old(
        'billing_month',
        isset($vouchers) && $vouchers->billing_month
            ? $vouchers->billing_month
            : date('Y-m')
    );
    try {
        $billingMonthValue = \Carbon\Carbon::parse($billingMonthRaw)->format('Y-m');
    } catch (\Throwable $e) {
        $billingMonthValue = date('Y-m');
    }

    $transDateValue = old(
        'trans_date',
        isset($vouchers->trans_date) ? date('Y-m-d', strtotime($vouchers->trans_date)) : date('Y-m-d')
    );
    $bankAccounts = \App\Models\Accounts::bankAccountsDropdown();
    $currencyCode = \App\Helpers\Currency::code();
@endphp

<div class="uvf-form funds-transfer-form"
    data-uvf-journal="1"
    data-modal-title="Funds Transfer"
    data-modal-subtitle="Transfer funds between bank accounts">

    <div class="rpf-header" data-rpf-inline-header>
        <h4 class="rpf-title">Funds Transfer</h4>
        <p class="rpf-subtitle">Transfer funds between bank accounts</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="trans_date">Date <span class="text-danger">*</span></label>
                    {!! Form::date('trans_date', $transDateValue, ['class' => 'form-control rpf-input', 'id' => 'trans_date', 'required' => true]) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="payment_type">Payment Mode <span class="text-danger">*</span></label>
                    {!! Form::select('payment_type', \App\Helpers\Account::payment_type_list(), null, ['class' => 'form-control select2 rpf-input', 'id' => 'payment_type', 'required' => true]) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="billing_month">Billing Month <span class="text-danger">*</span></label>
                    {!! Form::month('billing_month', $billingMonthValue, ['class' => 'form-control rpf-input', 'id' => 'billing_month', 'required' => true]) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="reference_number">Reference <span class="text-danger">*</span></label>
                    {!! Form::text('reference_number', null, ['class' => 'form-control rpf-input', 'id' => 'reference_number', 'maxlength' => 255, 'placeholder' => 'Enter reference number', 'required' => true]) !!}
                </div>
                @if(isset($rider) && $rider)
                <div class="form-group col-md-6">
                    <label class="rpf-label">Branch</label>
                    @php
                        $branchid = $rider->branch_id ?? null;
                        $branch = auth()->user()->branchByid($branchid);
                    @endphp
                    <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                    <input type="text" class="form-control rpf-input bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
                </div>
                @elseif(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="branch_id">Branch</label>
                    <select class="form-control select2 rpf-input" id="branch_id" name="branch_id" required>
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('branch_id', isset($vouchers) ? ($vouchers->branch_id ?? request('branch_id')) : request('branch_id')) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="form-group col-md-6">
                    <label class="rpf-label">Branch</label>
                    @php
                        $branchids = app('user_branches') ?? [];
                        $branchid = $branchids[0] ?? null;
                        $branch = auth()->user()->branchByid($branchid);
                    @endphp
                    <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                    <input type="text" class="form-control rpf-input bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
                </div>
                @endif
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="from_account_id">Sending Account <span class="text-danger">*</span></label>
                    {!! Form::select('from_account_id', $bankAccounts, $fromAccountId, ['class' => 'form-control select2 rpf-input', 'id' => 'from_account_id', 'required' => true]) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="to_account_id">Receiving Account <span class="text-danger">*</span></label>
                    {!! Form::select('to_account_id', $bankAccounts, $toAccountId, ['class' => 'form-control select2 rpf-input', 'id' => 'to_account_id', 'required' => true]) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="transfer_amount">Transfer Amount <span class="text-danger">*</span></label>
                    <div class="input-group rpf-amount-group">
                        <span class="input-group-text">{{ $currencyCode }}</span>
                        {!! Form::number('amount', $transferAmountValue, [
                            'class' => 'form-control rpf-input',
                            'step' => 'any',
                            'min' => '0.01',
                            'placeholder' => '0.00',
                            'id' => 'transfer_amount',
                            'required' => true,
                        ]) !!}
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="bank_charges">Bank Charges</label>
                    <div class="input-group rpf-amount-group">
                        <span class="input-group-text">{{ $currencyCode }}</span>
                        {!! Form::number('bank_charges', $bankChargesValue, [
                            'class' => 'form-control rpf-input',
                            'step' => 'any',
                            'min' => '0',
                            'placeholder' => '0.00',
                            'id' => 'bank_charges',
                        ]) !!}
                    </div>
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
                    'existingUrl' => !empty($vouchers?->attach_file) ? url('storage/vouchers/' . ltrim($vouchers->attach_file, '/')) : null,
                    'existingName' => !empty($vouchers?->attach_file) ? basename($vouchers->attach_file) : null,
                ])
                <div class="rpf-attach-hint">Multiple pages combined into one PDF.</div>
            </div>
        </div>
    </div>

    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Narration / Description</h6>
        <div class="form-group mb-0">
            <label class="rpf-label" for="description">Main Narration / Description</label>
            {!! Form::textarea('description', $descriptionValue, [
                'class' => 'form-control rpf-input',
                'id' => 'description',
                'rows' => 3,
                'placeholder' => 'Enter main description or notes about this transfer...',
            ]) !!}
        </div>
    </div>

    <div class="rpf-section mt-4">
        <div class="row g-3">
            <div class="col-lg-7"></div>
            <div class="col-lg-5">
                <div class="rpf-summary">
                    <div class="rpf-summary-row">
                        <span>Transfer Amount:</span>
                        <span>{{ $currencyCode }} <span id="display_amount">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Bank Charges:</span>
                        <span>{{ $currencyCode }} <span id="display_charges">0.00</span></span>
                    </div>
                    <div class="rpf-summary-total">
                        Total: {{ $currencyCode }} <span id="total_debit">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div data-funds-transfer-init data-currency="{{ $currencyCode }}" hidden></div>
@include('partials.uvf_styles')
