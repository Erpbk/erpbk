@push('third_party_stylesheets')
@endpush
<div id="visa-installments-inline-edit-scope">
    <table class="table table-striped dataTable no-footer" id="visaInstallmentsDataTable">
        <thead class="text-center">
            <tr role="row">
                <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
                <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
                <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                <th title="Narration" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Narration: activate to sort column ascending">Narration</th>
                <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
                <th title="Created By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Created By</th>
                <th title="Updated By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Updated By: activate to sort column ascending">Updated By</th>
                <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $installment)
            @php
            $installmentPendingDeletion = record_is_pending_deletion($installment);
            $voucherPendingDeletion = $installment->vouchers->contains(fn ($v) => record_is_pending_deletion($v));
            $rowPendingDeletion = $installmentPendingDeletion || $voucherPendingDeletion;
            @endphp
            <tr class="text-center {{ $rowPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $installment->id }}" data-status="{{ $installment->status }}">
                <td>
                    <span id="inst_date_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->date)->format('d M Y') }}</span>
                    @can('visa_expense_edit')
                    @if(!$rowPendingDeletion)
                    <a href="javascript:void(0);" onclick="editDate({{ $installment->id }})" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    @endcan
                    <input type="date"
                        id="inst_date_input_{{ $installment->id }}"
                        value="{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveDate({{ $installment->id }})"
                        onkeypress="if(event.keyCode==13) saveDate({{ $installment->id }})">
                </td>
                <td>
                    <span id="inst_voucher_ids_display_{{ $installment->id }}">
                        @if($installment->vouchers->isNotEmpty())
                        @foreach($installment->vouchers as $voucher)
                        <span class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucher->formatted_id }}</a>
                        </span>@if(!$loop->last), @endif
                        @endforeach
                        @else
                        {{ $installment->voucher_ids }}
                        @endif
                    </span>
                </td>
                <td>
                    <span id="inst_billing_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->billing_month)->format('M Y') }}</span>
                    @can('visa_expense_edit')
                    @if(!$rowPendingDeletion)
                    <a href="javascript:void(0);" onclick="editBillingMonth({{ $installment->id }})" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    @endcan
                    <input type="month"
                        id="inst_billing_input_{{ $installment->id }}"
                        value="{{ \Carbon\Carbon::parse($installment->billing_month)->format('Y-m') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveBillingMonth({{ $installment->id }})"
                        onkeypress="if(event.keyCode==13) saveBillingMonth({{ $installment->id }})">
                </td>
                <td>
                    <span id="inst_amount_display_{{ $installment->id }}">{{ number_format($installment->amount, 2) }}</span>
                    @can('visa_expense_edit')
                    @if(!$rowPendingDeletion)
                    <a href="javascript:void(0);" onclick="editAmount({{ $installment->id }})" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    @endcan
                    <input type="number"
                        step="0.01"
                        id="inst_amount_input_{{ $installment->id }}"
                        value="{{ $installment->amount }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveAmount({{ $installment->id }})"
                        onkeypress="if(event.keyCode==13) saveAmount({{ $installment->id }})">
                </td>
                <td class="text-start" style="min-width: 260px;">
                    <span id="inst_narration_display_{{ $installment->id }}">{!! $installment->transaction_narration ? $installment->transaction_narration : '-' !!}</span>
                    @can('visa_expense_edit')
                    @if(!$rowPendingDeletion)
                    <a href="javascript:void(0);" onclick="editNarration({{ $installment->id }})" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    @endcan
                    <textarea
                        id="inst_narration_input_{{ $installment->id }}"
                        rows="2"
                        data-original="{{ e($installment->transaction_narration ?? $installment->narration ?? '') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveNarration({{ $installment->id }})">{{ $installment->transaction_narration ?? $installment->narration ?? '' }}</textarea>
                </td>
                <td>{!! $installment->status_badge !!}</td>
                <td>
                    <span id="inst_created_by_display_{{ $installment->id }}">{{ $installment->created_by ? \App\Models\User::find($installment->created_by)->name :''}}</span>
                </td>
                <td>
                    <span id="inst_updated_by_display_{{ $installment->id }}">{{ $installment->updated_by ? \App\Models\User::find($installment->updated_by)->name :''}}</span>
                </td>
                <td>
                    @if($installmentPendingDeletion)
                    @include('delete_requests._locked_cell', ['model' => $installment])
                    @elseif($voucherPendingDeletion)
                    @include('delete_requests._locked_cell', ['model' => $installment->vouchers->first(fn ($v) => record_is_pending_deletion($v))])
                    @else
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $installment->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $installment->id }}">
                            @can('visa_expense_edit')
                            @if($installment->status === 'pending')
                            <a href="javascript:void(0);"
                                onclick="markAsPaid({{ $installment->id }})"
                                class='dropdown-item waves-effect'>
                                <i class="fa fa-check me-2"></i> Mark as Paid
                            </a>
                            @else
                            <a href="javascript:void(0);"
                                onclick="markAsPending({{ $installment->id }})"
                                class='dropdown-item waves-effect'>
                                <i class="fa fa-undo me-2"></i> Mark as Pending
                            </a>
                            @endif
                            @endcan
                            @can('visa_expense_delete')
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0);"
                                onclick='confirmDeleteProtected("{{ route('Installments.deleteInstallment', ['id' => $installment->id]) }}")'
                                class='dropdown-item waves-effect text-danger'>
                                <i class="fa fa-trash me-2"></i> Delete
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i>
                    No installment plans found. <br>
                    <small>Click "Create Installment Plan" to get started.</small>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($data->count() > 0)
        <tfoot>
            <tr class="bg-light">
                <td colspan="3" class="text-end"><strong>Total Amount Reference:</strong></td>
                <td class="text-center">
                    <strong>
                        @php
                        $totalAmount = $data->first()->total_amount ?? 0;
                        $currentTotal = $data->sum('amount');
                        $riderId = $data->first()->rider_id ?? null;
                        $paidTotal = $riderId ? \App\Models\visa_installment_plan::where('rider_id', $riderId)->where('status', 'paid')->sum('amount') : 0;
                        $pendingTotalAll = $riderId ? \App\Models\visa_installment_plan::where('rider_id', $riderId)->where('status', 'pending')->sum('amount') : 0;
                        @endphp
                        <span id="total-amount-reference">{{ number_format($totalAmount, 2) }}</span>
                        <br>
                    </strong>
                </td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif
    </table>
    {!! $data->links('pagination') !!}
</div>

<script>
    function installmentInlineScope() {
        return document.getElementById('visa-installments-inline-edit-scope');
    }

    function installmentFieldEl(field, installmentId) {
        const scope = installmentInlineScope();
        return scope ? scope.querySelector('#inst_' + field + '_' + installmentId) : null;
    }

    // Track unsaved amount changes locally until user finalizes
    let INSTALLMENT_AMOUNT_CHANGES = {};
    let INSTALLMENT_DELETIONS = {};
    let INSTALLMENT_ADDITIONS = [];
    let IS_FINALIZING = false;

    function markAsPaid(installmentId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to mark this installment as paid?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark as paid',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we update the status.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                submitForm('{{ route("Installments.payInstallment") }}', {
                    'installment_id': installmentId
                });
            }
        });
    }

    function markAsPending(installmentId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to mark this installment as pending?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark as pending',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we update the status.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                submitForm('{{ route("Installments.payInstallment") }}', {
                    'installment_id': installmentId,
                    'status': 'pending'
                });
            }
        });
    }

    // Edit functions - show input field
    function editDate(installmentId) {
        const dateDisplay = installmentFieldEl('date_display', installmentId);
        const dateInput = installmentFieldEl('date_input', installmentId);
        if (!dateDisplay || !dateInput) return;
        dateDisplay.classList.add('d-none');
        dateInput.classList.remove('d-none');
        dateInput.focus();
    }

    function editBillingMonth(installmentId) {
        const billingDisplay = installmentFieldEl('billing_display', installmentId);
        const billingInput = installmentFieldEl('billing_input', installmentId);
        if (!billingDisplay || !billingInput) return;
        billingDisplay.classList.add('d-none');
        billingInput.classList.remove('d-none');
        billingInput.focus();
    }

    function editAmount(installmentId) {
        const amountDisplay = installmentFieldEl('amount_display', installmentId);
        const amountInput = installmentFieldEl('amount_input', installmentId);
        if (!amountDisplay || !amountInput) return;
        amountDisplay.classList.add('d-none');
        amountInput.classList.remove('d-none');
        amountInput.focus();
        amountInput.select();
    }

    function editNarration(installmentId) {
        const narrationDisplay = installmentFieldEl('narration_display', installmentId);
        const narrationInput = installmentFieldEl('narration_input', installmentId);
        if (!narrationDisplay || !narrationInput) return;
        narrationDisplay.classList.add('d-none');
        narrationInput.classList.remove('d-none');
        narrationInput.focus();
        narrationInput.select();
    }

    // Save functions - hide input and save data
    function saveDate(installmentId) {
        const dateInput = installmentFieldEl('date_input', installmentId);
        const dateDisplay = installmentFieldEl('date_display', installmentId);
        if (!dateInput || !dateDisplay) return;
        const newValue = dateInput.value;
        const originalValue = dateInput.getAttribute('data-original') || '';
        const row = dateInput.closest('tr');
        const isPaid = row && row.getAttribute('data-status') === 'paid';

        if (newValue && newValue !== originalValue) {
            // Only validate date is not in past for pending installments
            if (!isPaid) {
                const selectedDate = new Date(newValue);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Date',
                        text: 'You cannot select a date in the past for pending installments.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                    dateInput.value = originalValue;
                    return;
                }
            }

            if (isPaid) {
                // For paid installments, update display and track change for finalization
                dateDisplay.textContent = new Date(newValue).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                dateInput.classList.add('d-none');
                dateDisplay.classList.remove('d-none');

                // Track the change for finalization
                if (!window.DATE_CHANGES) window.DATE_CHANGES = {};
                DATE_CHANGES[installmentId] = newValue;

                // Mark row as modified
                row.classList.add('bg-warning-subtle');
                row.setAttribute('data-modified', '1');
                return;
            } else {
                // For pending installments, confirm and submit directly
                Swal.fire({
                    title: 'Update Date?',
                    text: 'Are you sure you want to update the date? This will also update subsequent installments, voucher and transactions.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitForm('{{ route("Installments.updateInstallmentField") }}', {
                            'installment_id': installmentId,
                            'field': 'date',
                            'value': newValue,
                            'update_subsequent': true
                        });
                    } else {
                        // User cancelled, revert to original value
                        dateInput.value = originalValue;
                        dateInput.classList.add('d-none');
                        dateDisplay.classList.remove('d-none');
                    }
                });
                return;
            }
        }

        // Cancel edit
        dateInput.classList.add('d-none');
        dateDisplay.classList.remove('d-none');
    }

    function saveBillingMonth(installmentId) {
        const billingInput = installmentFieldEl('billing_input', installmentId);
        const billingDisplay = installmentFieldEl('billing_display', installmentId);
        if (!billingInput || !billingDisplay) return;
        const newValue = billingInput.value;
        const originalValue = billingInput.getAttribute('data-original') || '';

        if (newValue && newValue !== originalValue) {
            // Same flow for both paid and pending: confirm and submit directly
            Swal.fire({
                title: 'Update Billing Month?',
                text: 'Are you sure you want to update the billing month? This will also update subsequent installments, voucher and transactions.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, update it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('{{ route("Installments.updateInstallmentField") }}', {
                        'installment_id': installmentId,
                        'field': 'billing_month',
                        'value': newValue,
                        'update_subsequent': true
                    });
                } else {
                    // User cancelled, revert to original value
                    billingInput.value = originalValue;
                    billingInput.classList.add('d-none');
                    billingDisplay.classList.remove('d-none');
                }
            });
            return;
        }

        // Cancel edit
        billingInput.classList.add('d-none');
        billingDisplay.classList.remove('d-none');
    }

    function saveAmount(installmentId) {
        const amountInput = installmentFieldEl('amount_input', installmentId);
        const amountDisplay = installmentFieldEl('amount_display', installmentId);
        if (!amountInput || !amountDisplay) return;
        const newValue = amountInput.value;
        const originalValue = amountInput.getAttribute('data-original') || '';
        const row = amountInput.closest('tr');
        const isPaid = row && row.getAttribute('data-status') === 'paid';

        // Validate amount is positive
        if (!newValue || parseFloat(newValue) <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Amount must be greater than 0.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
            amountInput.value = originalValue;
            validateAmountInput(amountInput);
            // Hide input, show display
            amountInput.classList.add('d-none');
            amountDisplay.classList.remove('d-none');
            return;
        }

        // If value changed, save to database
        if (newValue !== originalValue) {
            // Confirm before updating
            Swal.fire({
                title: 'Update Amount?',
                text: 'Are you sure you want to update the amount? This will also update related vouchers and transactions.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, update it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm('{{ route("Installments.updateInstallmentField") }}', {
                        'installment_id': installmentId,
                        'field': 'amount',
                        'value': newValue,
                        'update_subsequent': false
                    });
                } else {
                    // User cancelled, revert to original value
                    amountInput.value = originalValue;
                    amountInput.classList.add('d-none');
                    amountDisplay.classList.remove('d-none');
                }
            });
            return;
        }

        // Hide input, show display
        amountInput.classList.add('d-none');
        amountDisplay.classList.remove('d-none');
    }

    function saveNarration(installmentId) {
        const narrationInput = installmentFieldEl('narration_input', installmentId);
        const narrationDisplay = installmentFieldEl('narration_display', installmentId);
        if (!narrationInput || !narrationDisplay) return;
        const newValue = narrationInput.value.trim();
        const originalValue = (narrationInput.getAttribute('data-original') || narrationInput.defaultValue || '').trim();

        if (newValue === originalValue) {
            narrationInput.classList.add('d-none');
            narrationDisplay.classList.remove('d-none');
            return;
        }

        submitForm('{{ route("Installments.updateInstallmentField") }}', {
            'installment_id': installmentId,
            'field': 'narration',
            'value': newValue,
            'update_subsequent': false
        });
    }

    function submitForm(action, data) {
        try {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.style.display = 'none';

            // Add CSRF token - try meta tag first, fallback to Blade syntax
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            csrfToken.value = metaToken ? metaToken.getAttribute('content') : '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            // Add data
            for (const [key, value] of Object.entries(data)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            // Submit form
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error('Error submitting form:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while processing your request. Please try again.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Soft delete with cascade tracking confirmation
    function confirmDeleteProtected(url) {
        try {
            const match = url && url.match(/deleteInstallment\/(\d+)/);
            const installmentId = match && match[1] ? match[1] : null;
            if (!installmentId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to detect installment to delete.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            // Show confirmation dialog for delete-approval workflow
            Swal.fire({
                title: 'Delete Installment Plan?',
                html: '<p>Are you sure you want to request deletion of this installment plan?</p>' +
                    '<p class="text-muted small">Until an administrator approves:</p>' +
                    '<ul class="text-start text-muted small">' +
                    '<li>The installment entry stays visible and locked</li>' +
                    '<li>Related vouchers and transactions stay visible</li>' +
                    '<li>Nothing is removed from accounts until approval</li>' +
                    '</ul>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit delete request',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });

            return false;
        } catch (e) {
            console.error('Error in confirmDeleteProtected:', e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while processing the deletion request.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
    }

    // Store original values when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const scope = installmentInlineScope();
        if (!scope) return;

        const dateInputs = scope.querySelectorAll('[id^="inst_date_input_"]');
        const billingInputs = scope.querySelectorAll('[id^="inst_billing_input_"]');
        const amountInputs = scope.querySelectorAll('[id^="inst_amount_input_"]');
        const narrationInputs = scope.querySelectorAll('[id^="inst_narration_input_"]');

        dateInputs.forEach(input => {
            input.setAttribute('data-original', input.value);

            // Add event listener for date validation
            input.addEventListener('input', function() {
                validateDateInput(this);
            });
        });

        billingInputs.forEach(input => {
            input.setAttribute('data-original', input.value);

            // Add event listener for billing month validation
            input.addEventListener('input', function() {
                validateBillingMonthInput(this);
            });
        });

        amountInputs.forEach(input => {
            input.setAttribute('data-original', input.value);

            // Add event listener for amount validation
            input.addEventListener('input', function() {
                validateAmountInput(this);
            });
        });

        narrationInputs.forEach(input => {
            input.setAttribute('data-original', input.value);
        });
    });

    // Client-side validation functions
    function validateDateInput(input) {
        const selectedDate = new Date(input.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const row = input.closest('tr');
        const isPaid = row && row.getAttribute('data-status') === 'paid';

        // Only validate date is not in past for pending installments
        if (!isPaid && selectedDate < today) {
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#f8d7da';
            input.title = 'Cannot select a date in the past for pending installments';
        } else {
            input.style.borderColor = '#28a745';
            input.style.backgroundColor = '#d4edda';
            input.title = '';
        }
    }

    function validateBillingMonthInput(input) {
        if (!input.value) {
            input.style.borderColor = '';
            input.style.backgroundColor = '';
            input.title = '';
            return;
        }

        input.style.borderColor = '#28a745';
        input.style.backgroundColor = '#d4edda';
        input.title = '';
    }

    function validateAmountInput(input) {
        const amount = parseFloat(input.value);

        if (isNaN(amount) || amount <= 0) {
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#f8d7da';
            input.title = 'Amount must be greater than 0';
        } else {
            input.style.borderColor = '#28a745';
            input.style.backgroundColor = '#d4edda';
            input.title = '';
        }
    }


    // Format currency (2 decimals, thousands separator)
    function formatCurrency(val) {
        try {
            return (val || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } catch (e) {
            return parseFloat(val).toFixed(2);
        }
    }


    // Public API to add a new installment (client-side only; requires finalize)
    function addNewInstallment(billingMonth, date, amount) {
        const val = parseFloat(amount);
        if (isNaN(val) || val <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Invalid amount for new installment.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
            return;
        }
        INSTALLMENT_ADDITIONS.push({
            billing_month: billingMonth,
            date: date,
            amount: val
        });
    }
</script>


<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Riders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="searchTopbody">
                <div style="display: none;" class="loading-overlay" id="loading-overlay">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <form id="filterForm" action="{{ route('banks.index') }}" method="GET">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <input type="number" name="search" class="form-control" placeholder="Search">
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@include('delete_requests._pending_table_script', ['items' => $data])