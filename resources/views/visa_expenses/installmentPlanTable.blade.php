@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
            <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
            <th title="Created By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Created By</th>
            <th title="Updated By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Updated By: activate to sort column ascending">Updated By</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">
                Action
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $installment)
        <tr class="text-center" data-status="{{ $installment->status }}">
            <td>
                <span id="date_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->date)->format('d M Y') }}</span>
                @can('visaloan_edit')
                <a href="javascript:void(0);" onclick="editDate({{ $installment->id }})" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                @endcan
                <input type="date"
                    id="date_input_{{ $installment->id }}"
                    value="{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}"
                    min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    class="form-control form-control-sm d-none"
                    onblur="saveDate({{ $installment->id }})"
                    onkeypress="if(event.keyCode==13) saveDate({{ $installment->id }})">
            </td>
            <td>
                <span id="voucher_ids_display_{{ $installment->id }}">
                    @if($installment->vouchers->isNotEmpty())
                    @foreach($installment->vouchers as $voucher)
                    <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucher->formatted_id }}</a>@if(!$loop->last), @endif
                    @endforeach
                    @else
                    {{ $installment->voucher_ids }}
                    @endif
                </span>
            </td>
            <td>
                <span id="billing_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->billing_month)->format('M Y') }}</span>
                @can('visaloan_edit')
                <a href="javascript:void(0);" onclick="editBillingMonth({{ $installment->id }})" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                @endcan
                <input type="month"
                    id="billing_input_{{ $installment->id }}"
                    value="{{ \Carbon\Carbon::parse($installment->billing_month)->format('Y-m') }}"
                    min="{{ \Carbon\Carbon::today()->format('Y-m') }}"
                    class="form-control form-control-sm d-none"
                    onblur="saveBillingMonth({{ $installment->id }})"
                    onkeypress="if(event.keyCode==13) saveBillingMonth({{ $installment->id }})">
            </td>
            <td>
                <span id="amount_display_{{ $installment->id }}">{{ number_format($installment->amount, 2) }}</span>
                @can('visaloan_edit')
                <a href="javascript:void(0);" onclick="editAmount({{ $installment->id }})" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                @endcan
                <input type="number"
                    step="0.01"
                    id="amount_input_{{ $installment->id }}"
                    value="{{ $installment->amount }}"
                    class="form-control form-control-sm d-none"
                    onblur="saveAmount({{ $installment->id }})"
                    onkeypress="if(event.keyCode==13) saveAmount({{ $installment->id }})">
            </td>
            <td>{!! $installment->status_badge !!}</td>
            <td>
                <span id="created_by_display_{{ $installment->id }}">{{ $installment->created_by ? \App\Models\User::find($installment->created_by)->name :''}}</span>
            </td>
            <td>
                <span id="updated_by_display_{{ $installment->id }}">{{ $installment->updated_by ? \App\Models\User::find($installment->updated_by)->name :''}}</span>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown{{ $installment->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown{{ $installment->id }}">
                        @can('visaloan_edit')
                        @if($installment->status === 'pending')
                        <a href="javascript:void(0);"
                            onclick="markAsPaid({{ $installment->id }})"
                            class='dropdown-item waves-effect'>
                            <i class="fa fa-check me-2"></i> Mark as Paid
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0);"
                            onclick='confirmDeleteProtected("{{ route('VisaExpense.deleteInstallment', $installment->id) }}")'
                            class='dropdown-item waves-effect text-danger'>
                            <i class="fa fa-trash me-2"></i> Delete
                        </a>
                        @else
                        <a href="javascript:void(0);"
                            onclick="markAsPending({{ $installment->id }})"
                            class='dropdown-item waves-effect'>
                            <i class="fa fa-undo me-2"></i> Mark as Pending
                        </a>
                        @endif
                        @else
                        <span class="dropdown-item-text text-{{ $installment->status === 'paid' ? 'success' : 'warning' }}">
                            <i class="fa fa-{{ $installment->status === 'paid' ? 'check' : 'clock' }} me-2"></i> {{ ucfirst($installment->status) }}
                        </span>
                        @endcan
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-4">
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
            <td colspan="2" class="text-end"><strong>Total Amount Reference:</strong></td>
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
                    <small id="current-total-amount-container" class="text-warning">(Current: <span>{{ number_format($currentTotal, 2) }}</span>)</small>
                </strong>
            </td>
            <td colspan="5"></td>
        </tr>
    </tfoot>
    @endif
</table>
{!! $data->links('pagination') !!}

<script>
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
                submitForm('{{ route("VisaExpense.payInstallment") }}', {
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
                submitForm('{{ route("VisaExpense.payInstallment") }}', {
                    'installment_id': installmentId,
                    'status': 'pending'
                });
            }
        });
    }

    // Edit functions - show input field
    function editDate(installmentId) {
        document.getElementById('date_display_' + installmentId).classList.add('d-none');
        document.getElementById('date_input_' + installmentId).classList.remove('d-none');
        document.getElementById('date_input_' + installmentId).focus();
    }

    function editBillingMonth(installmentId) {
        document.getElementById('billing_display_' + installmentId).classList.add('d-none');
        document.getElementById('billing_input_' + installmentId).classList.remove('d-none');
        document.getElementById('billing_input_' + installmentId).focus();
    }

    function editAmount(installmentId) {
        document.getElementById('amount_display_' + installmentId).classList.add('d-none');
        document.getElementById('amount_input_' + installmentId).classList.remove('d-none');
        document.getElementById('amount_input_' + installmentId).focus();
        document.getElementById('amount_input_' + installmentId).select();
    }

    // Save functions - hide input and save data
    function saveDate(installmentId) {
        const newValue = document.getElementById('date_input_' + installmentId).value;
        const originalValue = document.getElementById('date_input_' + installmentId).getAttribute('data-original') || '';
        const dateInput = document.getElementById('date_input_' + installmentId);
        const dateDisplay = document.getElementById('date_display_' + installmentId);
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
                        submitForm('{{ route("VisaExpense.updateInstallmentField") }}', {
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
        const newValue = document.getElementById('billing_input_' + installmentId).value;
        const originalValue = document.getElementById('billing_input_' + installmentId).getAttribute('data-original') || '';
        const billingInput = document.getElementById('billing_input_' + installmentId);
        const billingDisplay = document.getElementById('billing_display_' + installmentId);
        const row = billingInput.closest('tr');
        const isPaid = row && row.getAttribute('data-status') === 'paid';

        if (newValue && newValue !== originalValue) {
            // Only validate month is not in past for pending installments
            if (!isPaid) {
                const selectedDate = new Date(newValue + '-01');
                const today = new Date();
                const currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);

                if (selectedDate < currentMonth) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Billing Month',
                        text: 'You cannot select a billing month in the past for pending installments.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                    billingInput.value = originalValue;
                    return;
                }
            }

            if (isPaid) {
                // For paid installments, update display and track change for finalization
                const formattedDate = new Date(newValue + '-01').toLocaleDateString('en-GB', {
                    month: 'short',
                    year: 'numeric'
                });
                billingDisplay.textContent = formattedDate;
                billingInput.classList.add('d-none');
                billingDisplay.classList.remove('d-none');

                // Track the change for finalization
                if (!window.BILLING_CHANGES) window.BILLING_CHANGES = {};
                BILLING_CHANGES[installmentId] = newValue;

                // Mark row as modified
                row.classList.add('bg-warning-subtle');
                row.setAttribute('data-modified', '1');
                return;
            } else {
                // For pending installments, confirm and submit directly
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
                        submitForm('{{ route("VisaExpense.updateInstallmentField") }}', {
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
        }

        // Cancel edit
        billingInput.classList.add('d-none');
        billingDisplay.classList.remove('d-none');
    }

    function saveAmount(installmentId) {
        const newValue = document.getElementById('amount_input_' + installmentId).value;
        const originalValue = document.getElementById('amount_input_' + installmentId).getAttribute('data-original') || '';
        const amountInput = document.getElementById('amount_input_' + installmentId);
        const amountDisplay = document.getElementById('amount_display_' + installmentId);
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
                    submitForm('{{ route("VisaExpense.updateInstallmentField") }}', {
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

    function submitForm(action, data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
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
    }

    // Intercept deletion: mark as pending deletion and require finalize
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

            const amountInput = document.getElementById('amount_input_' + installmentId);
            const originalVal = amountInput ? parseFloat(amountInput.getAttribute('data-original')) : NaN;
            const originalAmount = isNaN(originalVal) ? 0 : originalVal;

            // Track deletion with original amount for diff calculations
            INSTALLMENT_DELETIONS[installmentId] = originalAmount;

            // Visually mark row as deleted and exclude from current total calculations
            const row = amountInput ? amountInput.closest('tr') : null;
            if (row) {
                row.setAttribute('data-deleted', '1');
                row.classList.add('table-danger');
                row.style.opacity = '0.6';
            }

            return false;
        } catch (e) {
            return false;
        }
    }

    // Store original values when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('[id^="date_input_"]');
        const billingInputs = document.querySelectorAll('[id^="billing_input_"]');
        const amountInputs = document.querySelectorAll('[id^="amount_input_"]');

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
        const selectedDate = new Date(input.value + '-01');
        const today = new Date();
        const currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const row = input.closest('tr');
        const isPaid = row && row.getAttribute('data-status') === 'paid';

        // Only validate month is not in past for pending installments
        if (!isPaid && selectedDate < currentMonth) {
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#f8d7da';
            input.title = 'Cannot select a billing month in the past for pending installments';
        } else {
            input.style.borderColor = '#28a745';
            input.style.backgroundColor = '#d4edda';
            input.title = '';
        }
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