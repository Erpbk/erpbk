
<form action="{{ route('rtaFines.payfine')}}" method="POST" id="formajax" enctype="multipart/form-data">
    @csrf
    {{-- Card Body --}}
    <div class="card-body">
        <div class="row">
            {{-- Left Column --}}
            <div class="col-md-6">
                {{-- Transaction Details --}}
                <div class="mb-4">
                    <h6 class="text-primary text-uppercase small fw-semibold mb-3">Fine Details</h6>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Ticket No:</span>
                            <strong>{{ $fine->ticket_no ?? 'N/A' }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Trip Date:</span>
                            <strong>{{ $fine->trip_date->format('d M Y') }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Trip Time:</span>
                            <strong>{{ $fine->trip_time->format('h:i:s a')  }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Plate No:</span>
                            <strong>{{ $fine->plate_no ?? 'N/A' }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Reference No:</span>
                            <strong>{{ $fine->reference_number ?? 'N/A' }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Paid By:</span>
                            <strong>{{ $fine->rider?->name ?? $fine->rentalCompany?->name ?? 'Us' }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2">
                        <div class="">
                            <span class="text-secondary">Description:</span>
                            <p class="mt-1 mb-0">{{ $fine->detail }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-md-6">
                {{-- Financial Details --}}
                <div class="mb-2">
                    <h6 class="text-primary text-uppercase small fw-semibold mb-3">Financial Details</h6>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Amount:</span>
                            <strong>{{ number_format($fine->amount ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Service Charges:</span>
                            <strong>{{ number_format($fine->service_charges ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Admin Fee:</span>
                            <strong>{{ number_format($fine->admin_fee ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">VAT:</span>
                            <strong>{{ number_format($fine->vat ?? 0, 2) }}</strong>
                        </div>
                    </div>
                    <div class="border-bottom pt-2 mt-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Total Amount:</span>
                            <strong class="text-danger h5 mb-0">{{ number_format($fine->total_amount ?? 0, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" value="{{ $fine->id }}" name="id">
        @php
            $isPaymentEdit = $isPaymentEdit ?? false;
            $selectedPayAccountId = old('pay_account', $selectedPayAccountId ?? '');
            $dateValue = old('trans_date', $isPaymentEdit ? ($paymentTransDate ?? '') : '');
            $billingMonthValue = old('billing_month', $isPaymentEdit ? ($paymentBillingMonth ?? '') : '');
            $referenceNumberValue = old('reference_number', $isPaymentEdit ? ($paymentReferenceNumber ?? '') : '');
        @endphp
        <div class="row mt-3">
            <div class="form-group col-md-6">
                <label>Debit Account</label>
                <input class="form-control" type="text" value="{{ $debitAccount ? ($debitAccount->account_code.'-'.$debitAccount->name) : '' }}" disabled>
            </div>
            <div class="form-group col-md-6">
                <label>Credit Account <span class="text-danger">*</span></label>
                <select class="form-select select2" required name="pay_account">
                    <option value="">Select An Account</option>
                    @foreach($creditAccounts as $a)
                    <option value="{{ $a->id }}" {{ (string) $selectedPayAccountId === (string) $a->id ? 'selected' : '' }}>
                        {{ $a->account_code }} - {{ $a->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Date <span class="text-danger">*</span></label>
                <input type="date" name="trans_date" class="form-control" value="{{ $dateValue }}" required>
            </div>
            <div class="form-group col-md-3">
                <label>Billing Month <span class="text-danger">*</span></label>
                <input type="month" name="billing_month" class="form-control" value="{{ $billingMonthValue }}" required>
            </div>
            <div class="form-group col-md-3">
                <label>Reference No</label>
                <input type="text" name="reference_number" class="form-control" value="{{ $referenceNumberValue }}" maxlength="255" placeholder="Reference No">
            </div>
            <div class="form-group col-md-3">
                @include('partials.universal_document_upload', [
                  'name' => 'attachment',
                  'label' => 'Attachment',
                  'required' => false,
                  'accept' => '.jpg,.jpeg,.png,.pdf,image/jpeg,image/png',
                  'inputClass' => 'form-control',
                  'showHint' => true,
                ])
            </div>
        </div>

        {{-- Attachment Section --}}
        @if($fine->attachment_path || ($isPaymentEdit && $fine->attachment))
        <div class="mt-3 pt-3 border-top">
            @if($fine->attachment_path)
            <div class="d-flex align-items-center mb-2">
                <svg class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="text-secondary me-2">Fine Attachment:</span>
                <a href="{{ storage_url($fine->attachment_path) }}"
                   target="_blank"
                   class="text-primary text-decoration-none">
                    View Attachment
                </a>
            </div>
            @endif
            @if($isPaymentEdit && $fine->attachment)
            <div class="d-flex align-items-center">
                <svg class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="text-secondary me-2">Payment Attachment:</span>
                <a href="{{ storage_url($fine->attachment) }}"
                   target="_blank"
                   class="text-primary text-decoration-none">
                    View Attachment
                </a>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Card Footer (Optional Actions) --}}
    <div class="card-footer">
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-primary" type="submit">
                {{ ($isPaymentEdit ?? false) ? 'Update Payment' : 'Pay Fine' }}
            </button>
        </div>
    </div>
</form>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTopbody')
    });
});
</script>