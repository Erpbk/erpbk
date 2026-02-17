<form action="{{ route('cheques.update', $cheque->id) }}" method="POST" enctype="multipart/form-data" id="formajax">
    @csrf
    @method('PUT')

    
    <!-- Cheque Form -->
    <div id="chequeFormStep">
        <!-- Basic Information -->
        <div class="row">
            <div class="col-md-12">
                {!! Form::checkbox('is_security', true, null, [
                    'class' => 'form-check-input'
                ]) !!}
                {!! Form::label('is_security', 'Security Cheque', [
                    'class' => 'fw-bold'
                ]) !!}
            </div>
        
            <!-- Parties Section -->
            <div id="partiesSection">
                <div class="row">
                <div class="col-md-6">
                    {!! Form::label('cheque_number', 'Cheque Number', ['class' => ['form-label', 'required']]) !!}
                    {!! Form::text('cheque_number', old('cheque_number', $cheque->cheque_number), [
                        'class' => 'form-control' . ($errors->has('cheque_number') ? ' is-invalid' : ''),
                        'required' => true,
                        'placeholder' => 'Enter cheque number'
                    ]) !!}
                    @error('cheque_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if($cheque->type == 'payable')
                    <div class="col-md-6">
                        <label for="payee_account" class="form-label required">Payee Account</label>
                        <select name="payee_account" class="form-control select2" required>
                            <option value="">Select</option>
                            @foreach(\App\Models\Accounts::where('status', 1)->get() as $payee)
                            <option value="{{ $payee->id }}" 
                                {{ $cheque->payee_account == $payee->id ? 'selected' : '' }}>
                                {{ $payee->account_code.'-'.$payee->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-6">
                        <label for="payer_account" class="form-label required">Payer Account</label>
                        <select name="payer_account" class="form-control select2" required>
                            <option value="">Select</option>
                            @foreach(\App\Models\Accounts::where('status', 1)->get() as $payer)
                            <option value="{{ $payer->id }}" 
                                {{ $cheque->payer_account == $payer->id || old('payer_account') == $payer->id ? 'selected' : '' }}>
                                {{ $payer->account_code.'-'.$payer->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                </div>
            </div>
            <div class="col-md-6">
                {!! Form::label('bank_id', 'Bank', ['class' => 'form-label']) !!}
                <select name="bank_id" id="bank_id" class="form-control select2" required>
                    <option value="">Select Bank</option>
                    @foreach(App\Models\Banks::where('status', 1)->get() as $bank)
                        <option value="{{ $bank->id }}" 
                            {{ $cheque->bank_id == $bank->id ? 'selected' : '' }}>{{ $bank->name }}
                        </option>
                    @endforeach
                </select>
                @error('bank_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6">
                {!! Form::label('amount', 'Amount', ['class' => ['form-label', 'required']]) !!}
                <div class="input-group">
                    <span class="input-group-text">AED</span>
                    {!! Form::number('amount', old('amount', $cheque->amount), [
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

            <!-- Reference & Issued By -->
            <div class="col-md-6">
                {!! Form::label('reference', 'Reference Number', ['class' => 'form-label']) !!}
                {!! Form::text('reference', old('reference', $cheque->reference), [
                    'class' => 'form-control' . ($errors->has('reference') ? ' is-invalid' : ''),
                    'placeholder' => 'Enter reference number'
                ]) !!}
                @error('reference')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-6">
                    {!! Form::label('cheque_date', 'Cheque Date', ['class' => ['form-label']]) !!}
                    {!! Form::date('cheque_date', old('cheque_date', $cheque->cheque_date ?? null), [
                        'class' => 'form-control' . ($errors->has('cheque_date') ? ' is-invalid' : ''),
                    ]) !!}
                    @error('issued_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
        
            <!-- Dates Section -->
            <div class="col-md-6">
                {!! Form::label('issue_date', 'Issue Date', ['class' => ['form-label', 'required']]) !!}
                {!! Form::date('issue_date', old('issue_date', $cheque->issue_date), [
                    'class' => 'form-control' . ($errors->has('issue_date') ? ' is-invalid' : ''),
                    'required' => true
                ]) !!}
                @error('issue_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        
            <div class="col-md-6">
                {!! Form::label('billing_month', 'Billing Month', ['class' => 'form-label']) !!}
                {!! Form::month('billing_month', old('billing_month', $cheque->billing_month), [
                    'class' => 'form-control' . ($errors->has('billing_month') ? ' is-invalid' : '')
                ]) !!}
                @error('billing_month')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Attachment & Bank -->
        <div class="row mb-3">
            <div class="col-md-6">
                {!! Form::label('attachment', 'Attachment', ['class' => ['form-label']]) !!}
                {!! Form::file('attachment', [
                    'class' => 'form-control' . ($errors->has('attachment') ? ' is-invalid' : ''),
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ]) !!}
                <div class="form-text"><span class="text-warning">Accepted:</span> PDF, JPG, JPEG, PNG - Max(2MB)</div>
                @error('attachment')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
        
        <!-- Additional Information -->
        <div class="row">
            <div class="col-12">
                {!! Form::label('description', 'Description', ['class' => 'form-label']) !!}
                {!! Form::textarea('description', old('description', $cheque->description), [
                    'class' => 'form-control' . ($errors->has('description') ? ' is-invalid' : ''),
                    'rows' => 2,
                    'placeholder' => 'Enter cheque description'
                ]) !!}
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Cheque
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
});
</script>

<style>
    .form-check .card {
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
    }
    
    .form-check .card.border-primary {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
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