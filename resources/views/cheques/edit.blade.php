<form action="{{ route('cheques.update', $cheque->id) }}" method="POST" enctype="multipart/form-data" id="formajax" data-rfp-entity="cheques">
    @csrf
    @method('PUT')

    
    <!-- Cheque Form -->
    <div id="chequeFormStep">
        <!-- Basic Information -->
        <div class="row">
            @fieldVisible('cheques', 'is_security')
            <div class="col-md-12">
                {!! Form::checkbox('is_security', true, $cheque->is_security, [
                    'class' => 'form-check-input'
                ] + field_lock('cheques', 'is_security', 'select')) !!}
                {!! Form::label('is_security', 'Security Cheque', [
                    'class' => 'fw-bold'
                ]) !!}
            </div>
            @endfieldVisible
        
            <!-- Parties Section -->
            <div id="partiesSection">
                <div class="row">
                @fieldVisible('cheques', 'cheque_number')
                <div class="col-md-6">
                    {!! Form::label('cheque_number', 'Cheque Number', ['class' => ['form-label', 'required']]) !!}
                    {!! Form::text('cheque_number', old('cheque_number', $cheque->cheque_number), [
                        'class' => 'form-control' . ($errors->has('cheque_number') ? ' is-invalid' : ''),
                        'required' => true,
                        'placeholder' => 'Enter cheque number'
                    ] + field_lock('cheques', 'cheque_number')) !!}
                    @error('cheque_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endfieldVisible
                @if($cheque->type == 'payable')
                    @fieldVisible('cheques', 'payee_account')
                    <div class="col-md-6">
                        <label for="payee_account" class="form-label required">Payee Account</label>
                        <select name="payee_account" class="form-control select2" required @fieldReadonly('cheques', 'payee_account')>
                            <option value="">Select</option>
                            @foreach(\App\Models\Accounts::where('status', 1)->get() as $payee)
                            <option value="{{ $payee->id }}" 
                                {{ $cheque->payee_account == $payee->id ? 'selected' : '' }}>
                                {{ $payee->account_code.'-'.$payee->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endfieldVisible
                @else
                    @fieldVisible('cheques', 'payer_account')
                    <div class="col-md-6">
                        <label for="payer_account" class="form-label required">Payer Account</label>
                        <select name="payer_account" class="form-control select2" required @fieldReadonly('cheques', 'payer_account')>
                            <option value="">Select</option>
                            @foreach(\App\Models\Accounts::where('status', 1)->get() as $payer)
                            <option value="{{ $payer->id }}" 
                                {{ $cheque->payer_account == $payer->id || old('payer_account') == $payer->id ? 'selected' : '' }}>
                                {{ $payer->account_code.'-'.$payer->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endfieldVisible
                @endif
                </div>
            </div>
            @fieldVisible('cheques', 'bank_id')
            <div class="col-md-6">
                {!! Form::label('bank_id', 'Bank', ['class' => 'form-label']) !!}
                <select name="bank_id" id="bank_id" class="form-control select2" @fieldReadonly('cheques', 'bank_id')>
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
            @endfieldVisible

            @fieldVisible('cheques', 'amount')
            <div class="col-md-6">
                {!! Form::label('amount', 'Amount', ['class' => ['form-label', 'required']]) !!}
                <div class="input-group">
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                    {!! Form::number('amount', old('amount', $cheque->amount), [
                        'class' => 'form-control' . ($errors->has('amount') ? ' is-invalid' : ''),
                        'required' => true,
                        'step' => '0.01',
                        'min' => '0.01',
                        'placeholder' => '0.00'
                    ] + field_lock('cheques', 'amount')) !!}
                </div>
                @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endfieldVisible

            <!-- Reference & Issued By -->
            @fieldVisible('cheques', 'reference')
            <div class="col-md-6">
                {!! Form::label('reference', 'Reference Number', ['class' => 'form-label']) !!}
                {!! Form::text('reference', old('reference', $cheque->reference), [
                    'class' => 'form-control' . ($errors->has('reference') ? ' is-invalid' : ''),
                    'placeholder' => 'Enter reference number'
                ] + field_lock('cheques', 'reference')) !!}
                @error('reference')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endfieldVisible

            @fieldVisible('cheques', 'cheque_date')
            <div class="col-6">
                    {!! Form::label('cheque_date', 'Cheque Date', ['class' => ['form-label']]) !!}
                    {!! Form::date('cheque_date', old('cheque_date', $cheque->cheque_date ?? null), [
                        'class' => 'form-control' . ($errors->has('cheque_date') ? ' is-invalid' : ''),
                    ] + field_lock('cheques', 'cheque_date')) !!}
                    @error('issued_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
            </div>
            @endfieldVisible

            <!-- Dates Section -->
            @fieldVisible('cheques', 'issue_date')
            <div class="col-md-6">
                {!! Form::label('issue_date', 'Issue Date', ['class' => ['form-label', 'required']]) !!}
                {!! Form::date('issue_date', old('issue_date', $cheque->issue_date), [
                    'class' => 'form-control' . ($errors->has('issue_date') ? ' is-invalid' : ''),
                    'required' => true
                ] + field_lock('cheques', 'issue_date')) !!}
                @error('issue_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endfieldVisible

            @fieldVisible('cheques', 'billing_month')
            <div class="col-md-6">
                {!! Form::label('billing_month', 'Billing Month', ['class' => 'form-label']) !!}
                {!! Form::month('billing_month', old('billing_month', $cheque->billing_month), [
                    'class' => 'form-control' . ($errors->has('billing_month') ? ' is-invalid' : '')
                ] + field_lock('cheques', 'billing_month')) !!}
                @error('billing_month')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endfieldVisible
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
            @fieldVisible('cheques', 'description')
            <div class="col-12">
                {!! Form::label('description', 'Description', ['class' => 'form-label']) !!}
                {!! Form::textarea('description', old('description', $cheque->description), [
                    'class' => 'form-control' . ($errors->has('description') ? ' is-invalid' : ''),
                    'rows' => 2,
                    'placeholder' => 'Enter cheque description'
                ] + field_lock('cheques', 'description')) !!}
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endfieldVisible
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