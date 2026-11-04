@php
    $remaining = $cheque->remainingAmount();
    $clearedAmount = round((float) $cheque->cleared_amount, 2);
    $hasClears = $clearedAmount > 0.00001;
@endphp
<form action="{{ route('cheques.update-status', $cheque->id) }}" id="formajax" method="POST" class="row g-3" enctype="multipart/form-data">
    @csrf
    
    <!-- Current Status Info -->
    <div class="col-12 mb-3">
        <div class="alert alert-info p-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong class="me-2">Cheque:</strong> 
                    <span class="fw-bold">{{ $cheque->cheque_number }}</span>
                    <span class="badge bg-secondary ms-2">{{ \App\Helpers\Currency::format($cheque->amount, 2) }}</span>
                    @if($hasClears)
                        <span class="badge bg-info text-dark ms-1">Cleared {{ \App\Helpers\Currency::format($clearedAmount, 2) }}</span>
                        <span class="badge bg-warning text-dark ms-1">Remaining {{ \App\Helpers\Currency::format($remaining, 2) }}</span>
                    @endif
                </div>
                <div class="text-end">
                    @php
                        $badgeClasses = [
                            'Issued' => 'bg-primary',
                            'Partially Cleared' => 'bg-info',
                            'Cleared' => 'bg-success',
                            'Returned' => 'bg-danger',
                            'Stop Payment' => 'bg-warning',
                            'Lost' => 'bg-secondary',
                        ];
                    @endphp
                    <span>
                        Current: <span class="badge {{ $badgeClasses[$cheque->status] ?? 'bg-secondary' }}">{{ $cheque->status }}</span>  
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 clear-alert" style="display: none">
        <div class="alert alert-danger p-2">
            <i class="fas fa-info-circle me-2"></i>
            @if($cheque->type == 'receievable')
                Recording a clear will generate a receipt for the entered amount from <strong>{{ $cheque->payer->account_code .'-'. $cheque->payer->name }}</strong> to <strong>{{ $cheque->payee->account_code .'-'. $cheque->payee->name }}</strong>. Remaining balance: <strong>{{ \App\Helpers\Currency::format($remaining, 2) }}</strong>.
            @else
                Recording a clear will generate a payment for the entered amount from <strong>{{ $cheque->payer->account_code .'-'. $cheque->payer->name }}</strong> to <strong>{{ $cheque->payee->account_code .'-'. $cheque->payee->name}}</strong>. Remaining balance: <strong>{{ \App\Helpers\Currency::format($remaining, 2) }}</strong>.
            @endif
        </div>
    </div>
    
    <!-- Status Selection -->
    <div class="row">
        <div class="col-md-6">
            <label class="form-label fw-medium required">New Status</label>
            <select name="status" class="form-select status-select select2" required>
                <option value="">Select Status</option>
                <option value="Cleared">{{ $hasClears ? 'Clear Partial / Remaining' : 'Cleared' }}</option>
                @unless($hasClears)
                    <option value="Returned">Returned</option>
                    <option value="Stop Payment">Stop Payment</option>
                    <option value="Lost">Lost</option>
                @endunless
            </select>
        </div>
    </div>
    
    <!-- Cleared Date Field -->
    <div class="col-md-3 date-field cleared-date" style="display: none;">
        <label class="form-label fw-medium required">Cleared Date</label>
        <input type="date" name="cleared_date" class="form-control" 
               value="{{ $cheque->cleared_date ? \Carbon\Carbon::parse($cheque->cleared_date)->format('Y-m-d') : '' }}">
    </div>

    <div class="col-md-3 bank" style="display: none;">
        {!! Form::label('bank', 'Bank', ['class' => ['form-label', 'fw-medium', 'required']]) !!}
        <select name="bank_id" class="form-control select2">
            <option value="">Select</option>
            @foreach(\App\Models\Banks::where('status', 1)->get() as $bank)
            <option value="{{ $bank->id }}" @if($cheque->bank_id == $bank->id) selected @endif>{{ $bank->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 clear-amount-field" style="display: none;">
        <label class="form-label fw-medium required">Clear Amount</label>
        <input type="number" name="clear_amount" class="form-control" step="0.01" min="0.01"
               max="{{ $remaining }}" value="{{ number_format($remaining, 2, '.', '') }}"
               placeholder="Amount to clear">
    </div>

    @if($cheque->type === 'payable')
    <div class="col-md-3 bank-charges-field" style="display: none;">
        <label class="form-label fw-medium">Bank Charges</label>
        <input type="number" name="bank_charges" class="form-control" step="0.01" min="0"
               value="0" placeholder="0.00">
    </div>
    @endif
    
    <!-- Returned Date Field -->
    <div class="col-md-3 date-field returned-date" style="display: none;">
        <label class="form-label fw-medium required">Returned Date</label>
        <input type="date" name="returned_date" class="form-control" 
               value="{{ $cheque->returned_date ? \Carbon\Carbon::parse($cheque->returned_date)->format('Y-m-d') : '' }}">
    </div>
    
    <!-- Stop Payment Date Field -->
    <div class="col-md-3 date-field stop-date" style="display: none;">
        <label class="form-label fw-medium required">Stop Payment Date</label>
        <input type="date" name="stop_payment_date" class="form-control" 
               value="{{ $cheque->stop_payment_date ? \Carbon\Carbon::parse($cheque->stop_payment_date)->format('Y-m-d') : '' }}">
    </div>

    <!-- Lost Date Field -->
    <div class="col-md-3 date-field lost-date" style="display: none;">
        <label class="form-label fw-medium required">Lost Date</label>
        <input type="date" name="lost_date" class="form-control"
               value="{{ $cheque->lost_date ? \Carbon\Carbon::parse($cheque->lost_date)->format('Y-m-d') : '' }}">
    </div>
    
    <!-- Return Status Notes Field -->
    <div class="col-md-3 return-reason-field" style="display: none;">
        <label class="form-label fw-medium required">Notes</label>
        <input type="text" name="return_reason" class="form-control" 
               value="{{ $cheque->return_reason ?? '' }}" 
               placeholder="Enter status notes">
    </div>
    
    <!-- Stop Payment Status Notes Field -->
    <div class="col-md-3 stop-payment-reason-field" style="display: none;">
        <label class="form-label fw-medium required">Notes</label>
        <input type="text" name="stop_payment_reason" class="form-control" 
               value="{{ $cheque->stop_payment_reason ?? '' }}" 
               placeholder="Enter status notes">
    </div>

    <!-- Lost Status Notes Field -->
    <div class="col-md-3 lost-note-field" style="display: none;">
        <label class="form-label fw-medium required">Notes</label>
        <input type="text" name="lost_note" class="form-control"
               value="{{ $cheque->lost_note ?? '' }}"
               placeholder="Enter status notes">
    </div>

    <div class="col-md-3 billing-month-field" style="display: none;">
        <label class="form-label fw-medium required">Billing Month</label>
        <input type="month" name="billing_month" class="form-control"
               value="{{ old('billing_month', now()->format('Y-m')) }}">
    </div>

    <div class="col-md-6 description-field" style="display: none;">
        <label class="form-label fw-medium required">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="description Required..." >{{ $cheque->description ?? old('description') }}</textarea>
    </div>

    <div class="col-md-3 clear-attachment-field" style="display: none;">
        @include('partials.universal_document_upload', [
            'name' => 'attachment',
            'label' => 'Attachment',
            'required' => true,
            'accept' => '.pdf,.jpg,.jpeg,.png',
            'inputClass' => 'form-control',
            'errorKey' => 'attachment',
            'showHint' => true,
        ])
        <div class="form-text">Stored on the payment/receipt voucher. Accepted: PDF, JPG, JPEG, PNG - Max(2MB)</div>
    </div>
    
    <!-- Form Actions -->
    <div class="col-12 mt-4 pt-3 border-top">
        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Status
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });

    // Status change handler
    $('.status-select').on('change', function() {
        const status = $(this).val();
        
        // Hide all fields first
        $('.bank, .date-field, .return-reason-field, .stop-payment-reason-field, .lost-note-field, .billing-month-field, .clear-alert, .description-field, .clear-amount-field, .bank-charges-field, .clear-attachment-field').hide();
        
        // Show relevant fields based on status
        if (status === 'Cleared') {
            $('.cleared-date').show();
            $('.billing-month-field').show();
            $('.clear-alert').show();
            $('.description-field').show();
            $('.bank').show();
            $('.clear-amount-field').show();
            @if($cheque->type === 'payable')
            $('.bank-charges-field').show();
            @endif
            $('.clear-attachment-field').show();
        } else if (status === 'Returned') {
            $('.returned-date').show();
            $('.return-reason-field').show();
        } else if (status === 'Stop Payment') {
            $('.stop-date').show();
            $('.stop-payment-reason-field').show();
        } else if (status === 'Lost') {
            $('.lost-date').show();
            $('.lost-note-field').show();
        }
        
        // Set required attribute for notes/date fields
        if (status === 'Returned') {
            $('[name="returned_date"]').attr('required', true);
            $('[name="return_reason"]').attr('required', true);
            $('[name="stop_payment_reason"], [name="stop_payment_date"], [name="lost_date"], [name="lost_note"], [name="billing_month"], [name="cleared_date"], [name="description"], [name="clear_amount"], [name="bank_id"], [name="attachment"]').removeAttr('required');
        } else if (status === 'Stop Payment') {
            $('[name="stop_payment_date"]').attr('required', true);
            $('[name="stop_payment_reason"]').attr('required', true);
            $('[name="returned_date"], [name="return_reason"], [name="lost_date"], [name="lost_note"], [name="billing_month"], [name="cleared_date"], [name="description"], [name="clear_amount"], [name="bank_id"], [name="attachment"]').removeAttr('required');
        } else if (status === 'Lost') {
            $('[name="lost_date"]').attr('required', true);
            $('[name="lost_note"]').attr('required', true);
            $('[name="returned_date"], [name="return_reason"], [name="stop_payment_reason"], [name="stop_payment_date"], [name="billing_month"], [name="cleared_date"], [name="description"], [name="clear_amount"], [name="bank_id"], [name="attachment"]').removeAttr('required');
        } else if (status === 'Cleared') {
            $('[name="cleared_date"]').attr('required', true);
            $('[name="billing_month"]').attr('required', true);
            $('[name="description"]').attr('required', true);
            $('[name="clear_amount"]').attr('required', true);
            $('[name="bank_id"]').attr('required', true);
            $('[name="attachment"]').attr('required', true);
            $('[name="returned_date"], [name="return_reason"], [name="stop_payment_reason"], [name="stop_payment_date"], [name="lost_date"], [name="lost_note"]').removeAttr('required');
        } else {
            $('[name="returned_date"], [name="return_reason"], [name="stop_payment_reason"], [name="stop_payment_date"], [name="lost_date"], [name="lost_note"], [name="billing_month"], [name="cleared_date"], [name="description"], [name="clear_amount"], [name="bank_id"], [name="attachment"]').removeAttr('required');
        }
    });

    @if($hasClears)
    $('.status-select').val('Cleared').trigger('change');
    @endif
});
</script>

<style>
    .form-label.fw-medium {
        font-weight: 500;
        color: #495057;
    }
    
    .status-select:focus, .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }
    
    .date-field, .return-reason-field, .stop-payment-reason-field {
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-info {
        background-color: #e7f1ff;
        border-color: #b3d4ff;
        color: #084298;
    }
</style>
