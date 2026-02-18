<form action="{{ route('cheques.update-status', $cheque->id) }}" id="formajax" method="POST" class="row g-3">
    @csrf
    
    <!-- Current Status Info -->
    <div class="col-12 mb-3">
        <div class="alert alert-info p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong class="me-2">Cheque:</strong> 
                    <span class="fw-bold">{{ $cheque->cheque_number }}</span>
                    <span class="badge bg-secondary ms-2">AED {{ number_format($cheque->amount, 2) }}</span>
                </div>
                <div class="text-end">
                    @php
                        $badgeClasses = [
                            'Issued' => 'bg-primary',
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
                Marking this cheque as <strong>Cleared</strong> will automatically generate a receipt of <strong>AED {{ number_format($cheque->amount, 2) }}</strong> from <strong>{{ $cheque->payer->account_code .'-'. $cheque->payer->name }}</strong> to <strong>{{ $cheque->payee->account_code .'-'. $cheque->payee->name }}</strong>. Please ensure the Cheque reflects the correct payment details.
            @else
                Marking this cheque as <strong>Cleared</strong> will automatically generate a payment of <strong>AED {{ number_format($cheque->amount, 2) }}</strong> from <strong>{{ $cheque->payer->account_code .'-'. $cheque->payer->name }}</strong> to <strong>{{ $cheque->payee->account_code .'-'. $cheque->payee->name}}</strong>. Please ensure the Cheque reflects the correct payment details.
            @endif
        </div>
    </div>
    
    <!-- Status Selection -->
    <div class="row">
        <div class="col-md-6">
            <label class="form-label fw-medium required">New Status</label>
            <select name="status" class="form-select status-select select2" required>
                <option value="">Select Status</option>
                <option value="Cleared" {{ $cheque->status == 'Cleared' ? 'selected' : '' }}>Cleared</option>
                <option value="Returned" {{ $cheque->status == 'Returned' ? 'selected' : '' }}>Returned</option>
                <option value="Stop Payment" {{ $cheque->status == 'Stop Payment' ? 'selected' : '' }}>Stop Payment</option>
                <option value="Lost" {{ $cheque->status == 'Lost' ? 'selected' : '' }}>Lost</option>
            </select>
        </div>
    </div>
    
    <!-- Cleared Date Field -->
    <div class="col-md-6 date-field cleared-date" style="{{ $cheque->status != 'Cleared' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Cleared Date</label>
        <input type="date" name="cleared_date" class="form-control" 
               value="{{ $cheque->cleared_date ? \Carbon\Carbon::parse($cheque->cleared_date)->format('Y-m-d') : '' }}">
    </div>

    <div class="col-md-6 bank" style="{{ $cheque->status != 'Cleared' ? 'display: none;' : '' }}">
        {!! Form::label('bank', 'Bank', ['class' => ['form-label', 'fw-medium', 'required']]) !!}
        <select name="bank_id" class="form-control select2" required>
            <option value="">Select</option>
            @foreach(\App\Models\Banks::where('status', 1)->get() as $bank)
            <option value="{{ $bank->id }}" @if($cheque->bank_id == $bank->id) selected @endif>{{ $bank->name }}</option>
            @endforeach
        </select>
    </div>
    
    <!-- Returned Date Field -->
    <div class="col-md-6 date-field returned-date" style="{{ $cheque->status != 'Returned' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Returned Date</label>
        <input type="date" name="returned_date" class="form-control" 
               value="{{ $cheque->returned_date ? \Carbon\Carbon::parse($cheque->returned_date)->format('Y-m-d') : '' }}">
    </div>
    
    <!-- Stop Payment Date Field -->
    <div class="col-md-6 date-field stop-date" style="{{ $cheque->status != 'Stop Payment' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Stop Payment Date</label>
        <input type="date" name="stop_payment_date" class="form-control" 
               value="{{ $cheque->stop_payment_date ? \Carbon\Carbon::parse($cheque->stop_payment_date)->format('Y-m-d') : '' }}">
    </div>
    
    <!-- Return Reason Field -->
    <div class="col-md-6 return-reason-field" style="{{ $cheque->status != 'Returned' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Return Reason</label>
        <input type="text" name="return_reason" class="form-control" 
               value="{{ $cheque->return_reason ?? '' }}" 
               placeholder="Enter reason for cheque return">
    </div>
    
    <!-- Stop Payment Reason Field -->
    <div class="col-md-6 stop-payment-reason-field" style="{{ $cheque->status != 'Stop Payment' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Stop Payment Reason</label>
        <input type="text" name="stop_payment_reason" class="form-control" 
               value="{{ $cheque->stop_payment_reason ?? '' }}" 
               placeholder="Enter reason for stop payment">
    </div>

    <div class="col-md-6 billing-month-field" style="{{ $cheque->status != 'Cleared' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Billing Month</label>
        <input type="month" name="billing_month" class="form-control" 
               value="{{ $cheque->billing_month ? \Carbon\Carbon::parse($cheque->billing_month)->format('Y-m') : '' }}">
    </div>

    <div class="col-md-12 description-field" style="{{ $cheque->status != 'Cleared' ? 'display: none;' : '' }}">
        <label class="form-label fw-medium required">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="description Required..." >{{ $cheque->description ?? old('description') }}</textarea>
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
        $('.bank, .date-field, .return-reason-field, .stop-payment-reason-field, .billing-month-field, .clear-alert, .description-field').hide();
        
        // Show relevant fields based on status
        if (status === 'Cleared') {
            $('.cleared-date').show();
            $('.billing-month-field').show();
            $('.clear-alert').show();
            $('.description-field').show();
            $('.bank').show();
        } else if (status === 'Returned') {
            $('.returned-date').show();
            $('.return-reason-field').show();
        } else if (status === 'Stop Payment') {
            $('.stop-date').show();
            $('.stop-payment-reason-field').show();
        }
        
        // Set required attribute for reason fields
        if (status === 'Returned') {
            $('[name="returned_date"]').attr('required', true);
            $('[name="return_reason"]').attr('required', true);
            $('[name="stop_payment_reason"], [name="stop_payment_date"], [name="billing_month"], [name="cleared_date"], [name="description"]').removeAttr('required');
        } else if (status === 'Stop Payment') {
            $('[name="stop_payment_date"]').attr('required', true);
            $('[name="stop_payment_reason"]').attr('required', true);
            $('[name="returned_date"], [name="return_reason"], [name="billing_month"], [name="cleared_date"], [name="description"]').removeAttr('required');
        } else if (status === 'Cleared') {
            $('[name="cleared_date"]').attr('required', true);
            $('[name="billing_month"]').attr('required', true);
            $('[name="description"]').attr('required', true);
            $('[name="returned_date"], [name="return_reason"], [name="stop_payment_reason"], [name="stop_payment_date"]').removeAttr('required');
        } else {
            $('[name="returned_date"], [name="return_reason"], [name="stop_payment_reason"], [name="stop_payment_date"], [name="billing_month"], [name="cleared_date"], [name="description"]').removeAttr('required');
        }
    });
    
    // // Form submission
    // $('#statusChangeForm').on('submit', function(e) {
    //     e.preventDefault();
    //     const form = $(this);
    //     const btn = form.find('button[type="submit"]');
    //     const original = btn.html();
        
    //     btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        
    //     $.ajax({
    //         url: "{{ route('cheques.update-status', $cheque->id) }}",
    //         method: 'POST',
    //         data: form.serialize(),
    //         success: function(response) {
    //             if (response.success) {
    //                 // Show success message
    //                 Swal.fire({
    //                     icon: 'success',
    //                     title: 'Success!',
    //                     text: response.message,
    //                     timer: 2000,
    //                     showConfirmButton: false
    //                 }).then(() => {
    //                     // Close modal and reload
    //                     $('#statusChangeModal').modal('hide');
    //                     location.reload();
    //                 });
    //             } else {
    //                 Swal.fire({
    //                     icon: 'error',
    //                     title: 'Error!',
    //                     text: response.message
    //                 });
    //                 btn.prop('disabled', false).html(original);
    //             }
    //         },
    //         error: function(xhr) {
    //             let errorMessage = 'An error occurred. Please try again.';
                
    //             if (xhr.responseJSON && xhr.responseJSON.message) {
    //                 errorMessage = xhr.responseJSON.message;
    //             } else if (xhr.responseJSON && xhr.responseJSON.errors) {
    //                 // Handle validation errors
    //                 const errors = xhr.responseJSON.errors;
    //                 errorMessage = Object.values(errors).flat().join('<br>');
    //             }
                
    //             Swal.fire({
    //                 icon: 'error',
    //                 title: 'Error!',
    //                 html: errorMessage
    //             });
    //             btn.prop('disabled', false).html(original);
    //         }
    //     });
    // });
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