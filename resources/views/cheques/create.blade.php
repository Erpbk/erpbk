<form action="{{ route('cheques.store') }}" method="POST" enctype="multipart/form-data" id="chequeForm">
    @csrf
    <input type="hidden" name="bank_id" value="{{ $bank->id }}">
    
    <!-- Step 1: Type Selection -->
    <div id="typeSelectionStep" class="mb-4">
        <div class="text-center mb-4">
            <h6 class="text-muted mb-3">Select Cheque Type</h6>
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="form-check card border h-100">
                    <input class="form-check-input d-none" type="radio" name="type" id="type_payable" value="payable" required>
                    <label class="form-check-label card-body text-center d-flex flex-column justify-content-center" for="type_payable" style="cursor: pointer;">
                        <i class="fas fa-arrow-up text-primary mb-2 fs-4"></i>
                        <h6 class="mb-1">Payable</h6>
                        <p class="text-muted small mb-0">Money going out</p>
                    </label>
                </div>
            </div>
            <div class="col-6">
                <div class="form-check card border h-100">
                    <input class="form-check-input d-none" type="radio" name="type" id="type_receiveable" value="receiveable" required>
                    <label class="form-check-label card-body text-center d-flex flex-column justify-content-center" for="type_receiveable" style="cursor: pointer;">
                        <i class="fas fa-arrow-down text-success mb-2 fs-4"></i>
                        <h6 class="mb-1">Receiveable</h6>
                        <p class="text-muted small mb-0">Money coming in</p>
                    </label>
                </div>
            </div>
        </div>
        
        @error('type')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    
    <!-- Step 2: Cheque Form (Initially Hidden) -->
    <div id="chequeFormStep" style="display: none;">
        <!-- Basic Information -->
        <div class="row">
            <div class="col-md-6">
                    {!! Form::label('cheque_number', 'Cheque Number', ['class' => ['form-label', 'required']]) !!}
                    {!! Form::text('cheque_number', old('cheque_number'), [
                        'class' => 'form-control' . ($errors->has('cheque_number') ? ' is-invalid' : ''),
                        'required' => true,
                        'placeholder' => 'Enter cheque number'
                    ]) !!}
                    @error('cheque_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
            
            <div class="col-md-6">
                    {!! Form::label('amount', 'Amount', ['class' => ['form-label', 'required']]) !!}
                    <div class="input-group">
                        <span class="input-group-text">AED</span>
                        {!! Form::number('amount', old('amount'), [
                            'class' => 'form-control' . ($errors->has('amount') ? ' is-invalid' : ''),
                            'required' => true,
                            'step' => '0.01',
                            'min' => '0.01',
                            'placeholder' => '0.00'
                        ]) !!}
                    </div>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        </div>
        
        <!-- Dynamic Parties Section -->
        <div id="partiesSection">
            <!-- Payee fields will be shown for payable, Payer fields for receiveable -->
        </div>
        
        <!-- Dates Section -->
        <div class="row">
            <div class="col-md-6">
                    {!! Form::label('issue_date', 'Issue Date', ['class' => ['form-label', 'required']]) !!}
                    {!! Form::date('issue_date', old('issue_date'), [
                        'class' => 'form-control' . ($errors->has('issue_date') ? ' is-invalid' : ''),
                        'required' => true
                    ]) !!}
                    @error('issue_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
            
            <div class="col-md-6">
                    {!! Form::label('billing_month', 'Billing Month', ['class' => 'form-label']) !!}
                    {!! Form::month('billing_month', old('billing_month'), [
                        'class' => 'form-control' . ($errors->has('billing_month') ? ' is-invalid' : '')
                    ]) !!}
                    @error('billing_month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        </div>
        
        <!-- Reference & Status -->
        <div class="row">
            <div class="col-md-6">
                    {!! Form::label('reference', 'Reference Number', ['class' => 'form-label']) !!}
                    {!! Form::text('reference', old('reference'), [
                        'class' => 'form-control' . ($errors->has('reference') ? ' is-invalid' : ''),
                        'placeholder' => 'Enter reference number'
                    ]) !!}
                    @error('reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
            
            <div class="col-md-6">
                    {!! Form::label('voucher_id', 'Voucher ID', ['class' => 'form-label']) !!}
                    {!! Form::number('voucher_id', old('voucher_id'), [
                        'class' => 'form-control' . ($errors->has('voucher_id') ? ' is-invalid' : ''),
                        'placeholder' => 'Enter voucher ID'
                    ]) !!}
                    @error('voucher_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="row">
            <div class="col-12">
                    {!! Form::label('description', 'Description', ['class' => 'form-label']) !!}
                    {!! Form::textarea('description', old('description'), [
                        'class' => 'form-control' . ($errors->has('description') ? ' is-invalid' : ''),
                        'rows' => 2,
                        'placeholder' => 'Enter cheque description'
                    ]) !!}
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        </div>
        
        <!-- Attachment -->
        <div class="row">
            <div class="col-6">
                    {!! Form::label('attachment', 'Attachment', ['class' => 'form-label']) !!}
                    {!! Form::file('attachment', [
                        'class' => 'form-control' . ($errors->has('attachment') ? ' is-invalid' : ''),
                        'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
                    ]) !!}
                    <div class="form-text">Accepted: PDF, JPG, PNG, DOC (Max: 5MB)</div>
                    @error('attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" id="backToTypeBtn">
                <i class="fas fa-arrow-left me-1"></i> Back
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Cheque
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Handle type selection click
    $('input[name="type"]').on('change', function() {
        const type = $(this).val();
        
        // Hide type selection, show form
        $('#typeSelectionStep').hide();
        $('#chequeFormStep').show();
        
        // Update parties section
        if (type === 'payable') {
            $('#partiesSection').html(`
                <div class="row">
                    <div class="col-md-6">
                        <label for="payee_name" class="form-label">Payee Name</label>
                        <input type="text" name="payee_name" class="form-control" placeholder="Enter payee name">
                    </div>
                    <div class="col-md-6">
                        <label for="payee_account" class="form-label">Payee Account</label>
                        <input type="text" name="payee_account" class="form-control" placeholder="Enter account number">
                    </div>
                </div>
            `);
        } else if (type === 'receiveable') {
            $('#partiesSection').html(`
                <div class="row">
                    <div class="col-md-6">
                        <label for="payer_name" class="form-label">Payer Name</label>
                        <input type="text" name="payer_name" class="form-control" placeholder="Enter payer name">
                    </div>
                    <div class="col-md-6">
                        <label for="payer_account" class="form-label">Payer Account</label>
                        <input type="text" name="payer_account" class="form-control" placeholder="Enter account number">
                    </div>
                </div>
            `);
        }
        
        // Highlight selected card
        $('.form-check .card').removeClass('border-primary bg-light');
        $(this).closest('.card').addClass('border-primary bg-light');
    });
    
    // Back button handler
    $('#backToTypeBtn').on('click', function() {
        $('#chequeFormStep').hide();
        $('#typeSelectionStep').show();
        $('input[name="type"]').prop('checked', false);
        $('.form-check .card').removeClass('border-primary bg-light');
    });
    
    // If returning with validation errors
    @if(old('type'))
        $('input[name="type"][value="{{ old('type') }}"]').prop('checked', true).trigger('change');
    @endif
});
</script>

<style>
    .form-check .card {
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
    }
    
    .form-check .card:hover {
        border-color: #6c757d;
        transform: translateY(-2px);
    }
    
    .form-check .card.border-primary {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }
    
    .form-check .card.bg-light {
        background-color: #f8f9fa !important;
    }
    
    .form-check-label {
        cursor: pointer;
    }
    
    .card-header.bg-light {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6;
    }
    
    .input-group-text {
        background-color: #f8f9fa;
    }
</style>