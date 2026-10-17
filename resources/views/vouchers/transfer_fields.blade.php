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

<div class="px-1 funds-transfer-form">
    <div class="row">
        <div class="form-group col-md-3">
            {!! Form::label('trans_date', 'Date:') !!}
            {!! Form::date('trans_date', $transDateValue, ['class' => 'form-control', 'required' => true]) !!}
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('payment_type', 'Payment Mode:') !!}
            {!! Form::select('payment_type', \App\Helpers\Account::payment_type_list(), null, ['class' => 'form-control select2', 'required' => true]) !!}
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('billing_month', 'Billing Month:') !!}
            {!! Form::month('billing_month', $billingMonthValue, ['class' => 'form-control', 'id' => 'billing_month', 'required' => true]) !!}
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('reference_number', 'Reference:') !!}
            {!! Form::text('reference_number', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => 'Enter reference number', 'required' => true]) !!}
        </div>

        @if(isset($rider) && $rider)
            <div class="form-group col-md-3">
                {!! Form::label('branch_id', 'Branch:') !!}
                @php
                    $branchid = $rider->branch_id ?? null;
                    $branch = auth()->user()->branchByid($branchid);
                @endphp
                <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                <input type="text" class="form-control bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
            </div>
        @elseif(auth()->user()->hasMultiplebranches())
            <div class="form-group col-md-3">
                {!! Form::label('branch_id', 'Branch:') !!}
                <select class="form-control select2" id="branch_id" name="branch_id" required>
                    @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('branch_id', isset($vouchers) ? ($vouchers->branch_id ?? request('branch_id')) : request('branch_id')) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="form-group col-md-3">
                {!! Form::label('branch_id', 'Branch:') !!}
                @php
                    $branchids = app('user_branches') ?? [];
                    $branchid = $branchids[0] ?? null;
                    $branch = auth()->user()->branchByid($branchid);
                @endphp
                <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                <input type="text" class="form-control bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
            </div>
        @endif

        <div class="form-group col-md-3">
            @include('partials.universal_document_upload', [
                'name' => 'attachment',
                'label' => 'Attachment:',
                'required' => false,
                'inputClass' => 'form-control',
                'showHint' => true,
                'existingUrl' => !empty($vouchers?->attach_file) ? url('storage/vouchers/' . ltrim($vouchers->attach_file, '/')) : null,
                'existingName' => !empty($vouchers?->attach_file) ? basename($vouchers->attach_file) : null,
            ])
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <h6 class="bg-light p-2 mb-3">Transaction Details</h6>
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('from_account_id', 'Sending Account:') !!}
            {!! Form::select('from_account_id', $bankAccounts, $fromAccountId, ['class' => 'form-control select2', 'id' => 'from_account_id', 'required' => true]) !!}
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('to_account_id', 'Receiving Account:') !!}
            {!! Form::select('to_account_id', $bankAccounts, $toAccountId, ['class' => 'form-control select2', 'id' => 'to_account_id', 'required' => true]) !!}
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('amount', 'Transfer Amount:') !!}
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ $currencyCode }}</span>
                </div>
                {!! Form::number('amount', $transferAmountValue, [
                    'class' => 'form-control',
                    'step' => 'any',
                    'min' => '0.01',
                    'placeholder' => 'Enter amount',
                    'id' => 'transfer_amount',
                    'required' => true,
                ]) !!}
            </div>
        </div>

        <div class="form-group col-md-3">
            {!! Form::label('bank_charges', 'Bank Charges:') !!}
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ $currencyCode }}</span>
                </div>
                {!! Form::number('bank_charges', $bankChargesValue, [
                    'class' => 'form-control',
                    'step' => 'any',
                    'min' => '0',
                    'placeholder' => 'Enter bank charges',
                    'id' => 'bank_charges',
                ]) !!}
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="form-group col-md-7">
            {!! Form::label('description', 'Main Narration / Description:') !!}
            {!! Form::textarea('description', $descriptionValue, [
                'class' => 'form-control',
                'rows' => 4,
                'placeholder' => 'Enter main description or notes about this transfer...',
            ]) !!}
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-8 offset-md-4">
            <div class="card bg-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Transfer Amount:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ $currencyCode }} <span id="display_amount">0.00</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Bank Charges:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ $currencyCode }} <span id="display_charges">0.00</span>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ $currencyCode }} <span id="total_debit">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div data-funds-transfer-init data-currency="{{ $currencyCode }}" hidden></div>

<style>
.funds-transfer-form .form-group label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}
.funds-transfer-form .form-control:focus,
.funds-transfer-form .select2-container--default .select2-selection--single:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.funds-transfer-form .bg-light {
    background-color: #f8f9fa !important;
}
.funds-transfer-form .input-group-text {
    background-color: #e9ecef;
}
.funds-transfer-form hr {
    border-top: 1px solid rgba(0,0,0,0.1);
}
@media (max-width: 768px) {
    .funds-transfer-form .col-md-8.offset-md-4 {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>
