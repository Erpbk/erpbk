
<form action="{{ route('rtaFines.payfine')}}" method="POST" id="formajax">
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
                    <div class="border-bottom pb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Voucher:</span>
                            <a @if($fine->voucher_id) href="{{ route('vouchers.show', $fine->voucher_id ?? 0) }}" target="_blank" @else href="javascript:void(0);" @endif><strong>{{ $fine->voucher_id ? ($fine->voucher->voucher_type . '-' . str_pad($fine->voucher_id, 4, '0', STR_PAD_LEFT)) : 'N/A' }}</strong></a>
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

                {{-- Additional Info --}}
                <div class="mb-4">
                    <div class="form-group col-sm-12">
                        <label class="">Debit Account:</label>
                        <input class="form-control" type="text" value="{{ $debitAccount->account_code.'-'.$debitAccount->name }}" disabled>
                    </div>
                    <div class="form-group col-sm-12">
                        <label class="">Credit Account:</label>
                        <select class="form-select select2" required name="pay_account">
                            <option value="">Select An Account</option>
                            @foreach($creditAccounts as $a)
                            <option value="{{ $a->id }}">
                                {{ $a->account_code }} - {{ $a->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" value="{{ $fine->id }}" name="id">
                    <div class="form-group col-sm-12">
                        <label>Attachment</label>
                        <input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png">
                    </div>
                </div>
            </div>
        </div>

        {{-- Attachment Section --}}
        @if($fine->attachment_path)
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center">
                <svg class="me-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="text-secondary me-2">Attachment:</span>
                <a href="{{ Storage::url($fine->attachment_path) }}" 
                   target="_blank" 
                   class="text-primary text-decoration-none">
                    View Attachment
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Card Footer (Optional Actions) --}}
    <div class="card-footer">
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-primary" type="submit">
                Pay Fine
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