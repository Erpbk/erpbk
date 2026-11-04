@push('third_party_stylesheets')
@endpush
@php
$installmentPayRoute = $installmentPayRoute ?? 'Installments.payInstallment';
$installmentUpdateFieldRoute = $installmentUpdateFieldRoute ?? 'Installments.updateInstallmentField';
$installmentDeleteRoute = $installmentDeleteRoute ?? 'Installments.deleteInstallment';
$installmentBulkDeleteRoute = $installmentBulkDeleteRoute ?? 'Installments.bulkDeleteInstallments';
$installmentPaymentReceivingRoute = $installmentPaymentReceivingRoute ?? 'Installments.paymentReceivingModal';
$installmentEditPaymentReceivingRoute = $installmentEditPaymentReceivingRoute ?? 'Installments.editPaymentReceiving';
$canEditInstallment = $canEditInstallment ?? \App\Support\VisaExpenseAccess::canManageInstallments('edit');
$canDeleteInstallment = $canDeleteInstallment ?? \App\Support\VisaExpenseAccess::canManageInstallments('delete');
$installmentPlanModel = $installmentPlanModel ?? \App\Models\visa_installment_plan::class;
$installmentTableScope = $installmentTableScope
    ?? \Illuminate\Support\Str::kebab(class_basename($installmentPlanModel));
$linkedReceiptsById = $linkedReceiptsById ?? collect();
@endphp
<div class="riders-list-demo" id="{{ $installmentTableScope }}-inline-edit-scope"
     data-bulk-delete-url="{{ route($installmentBulkDeleteRoute) }}">
    @if($canDeleteInstallment)
    <div class="d-flex justify-content-end mb-2 px-1">
        <button type="button" class="btn btn-sm btn-outline-danger js-installment-delete-selected" id="{{ $installmentTableScope }}-delete-selected" style="display:none;" onclick="deleteSelectedInstallments(this)">
            <i class="fa fa-trash me-1"></i> Delete Selected
        </button>
    </div>
    @endif
    <div class="rl-tbox">
    <table class="table" id="{{ $installmentTableScope }}-data-table">
        <thead class="text-center">
            <tr id="rlHead" role="row">
                @if($canDeleteInstallment)
                <th class="nosort" title="Select All" width="50px">
                    <input type="checkbox" class="js-installment-select-all" id="{{ $installmentTableScope }}-select-all" onchange="toggleInstallmentSelectAll(this)" style="transform:scale(1.2);cursor:pointer;">
                </th>
                @endif
                <th title="Date" class="sorting" tabindex="0">Date <span class="ar">⇅</span></th>
                <th title="Voucher ID" class="sorting" tabindex="0">Voucher ID <span class="ar">⇅</span></th>
                <th title="Receipts" class="sorting" tabindex="0">Receipts <span class="ar">⇅</span></th>
                <th title="Billing Month" class="sorting" tabindex="0">Billing Month <span class="ar">⇅</span></th>
                <th title="Narration" class="sorting" tabindex="0">Narration <span class="ar">⇅</span></th>
                <th title="Amount" class="sorting" tabindex="0">Amount <span class="ar">⇅</span></th>
                <th title="Created By" class="sorting" tabindex="0">Created By <span class="ar">⇅</span></th>
                <th title="Updated By" class="sorting" tabindex="0">Updated By <span class="ar">⇅</span></th>
                <th title="Status" class="sorting" tabindex="0">Status <span class="ar">⇅</span></th>
                <th title="Action" class="nosort" width="120px">Action</th>
            </tr>
        </thead>
        <tbody id="rlBody">
            @forelse($data as $installment)
            @php
            $installmentPendingDeletion = record_is_pending_deletion($installment);
            $voucherPendingDeletion = $installment->vouchers->contains(fn ($v) => record_is_pending_deletion($v));
            $rowPendingDeletion = $installmentPendingDeletion || $voucherPendingDeletion;
            $partialsMap = is_array($installment->partial_paid_amount) ? $installment->partial_paid_amount : [];
            $rowLinkedReceiptIds = [];
            $rowLinkedReceipts = [];
            foreach ($partialsMap as $rid => $amt) {
                $rid = (int) $rid;
                if ($rid <= 0 || (float) $amt <= 0) {
                    continue;
                }
                $rowLinkedReceiptIds[] = $rid;
                if (isset($linkedReceiptsById[$rid])) {
                    $rowLinkedReceipts[] = $linkedReceiptsById[$rid];
                } else {
                    $rowLinkedReceipts[] = (object) [
                        'id' => $rid,
                        'voucher_id' => null,
                    ];
                }
            }
            // Gate edits on partial_paid_amount itself, not only preloaded receipt rows.
            $hasLinkedReceipts = $rowLinkedReceiptIds !== [];
            $canInlineEdit = $canEditInstallment && !$rowPendingDeletion && !$hasLinkedReceipts;
            @endphp
            <tr class="text-center {{ $rowPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $installment->id }}" data-status="{{ $installment->status }}">
                @if($canDeleteInstallment)
                <td>
                    @if(!$rowPendingDeletion)
                    <input type="checkbox" class="installment-checkbox" value="{{ $installment->id }}" onchange="updateInstallmentDeleteButton(this)" style="transform:scale(1.2);cursor:pointer;">
                    @endif
                </td>
                @endif
                <td>
                    <span id="inst_date_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->date)->format('d M Y') }}</span>
                    @if($canInlineEdit)
                    <a href="javascript:void(0);" onclick="editDate({{ $installment->id }}, this)" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    <input type="date"
                        id="inst_date_input_{{ $installment->id }}"
                        value="{{ \Carbon\Carbon::parse($installment->date)->format('Y-m-d') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveDate({{ $installment->id }}, this)"
                        onkeypress="if(event.keyCode==13) saveDate({{ $installment->id }}, this)">
                </td>
                <td>
                    <span id="inst_voucher_ids_display_{{ $installment->id }}">
                        @if($installment->vouchers->isNotEmpty())
                        @foreach($installment->vouchers as $voucher)
                        <span class="d-inline-flex align-items-center gap-1">
                            <a href="javascript:void(0);"
                                class="text-primary show-voucher-panel"
                                data-action="{{ route('vouchers.show', $voucher->id) }}"
                                data-title="{{ \App\Helpers\General::VoucherType($voucher->voucher_type) ?? $voucher->voucher_type }} #{{ $voucher->formatted_id }}"
                                data-collapse-sidebar="1">{{ $voucher->formatted_id }}</a>
                        </span>@if(!$loop->last), @endif
                        @endforeach
                        @else
                        {{ $installment->voucher_ids }}
                        @endif
                    </span>
                </td>
                <td>
                    @if($rowLinkedReceipts !== [])
                    <div class="d-flex flex-column gap-1 align-items-center">
                        @foreach($rowLinkedReceipts as $linkedReceipt)
                            @if($linkedReceipt->voucher_id)
                            <a href="javascript:void(0);"
                                data-action="{{ route('vouchers.show', $linkedReceipt->voucher_id) }}"
                                class="text-primary show-voucher-panel"
                                data-title="Receipt Voucher RV-{{ $linkedReceipt->voucher_id }}"
                                data-collapse-sidebar="1">
                                RV-{{ $linkedReceipt->voucher_id }}
                            </a>
                            @else
                            <span class="text-muted">Receipt #{{ $linkedReceipt->id }}</span>
                            @endif
                        @endforeach
                    </div>
                    @else
                    -
                    @endif
                </td>
                <td>
                    <span id="inst_billing_display_{{ $installment->id }}">{{ \Carbon\Carbon::parse($installment->billing_month)->format('M-Y') }}</span>
                    @if($canInlineEdit)
                    <a href="javascript:void(0);" onclick="editBillingMonth({{ $installment->id }}, this)" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    <input type="month"
                        id="inst_billing_input_{{ $installment->id }}"
                        value="{{ \Carbon\Carbon::parse($installment->billing_month)->format('Y-m') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveBillingMonth({{ $installment->id }}, this)"
                        onkeypress="if(event.keyCode==13) saveBillingMonth({{ $installment->id }}, this)">
                </td>
                <td class="text-start" style="min-width: 260px;">
                    <span id="inst_narration_display_{{ $installment->id }}">{!! $installment->narration ? $installment->narration : '-' !!}</span>
                    @if($canInlineEdit)
                    <a href="javascript:void(0);" onclick="editNarration({{ $installment->id }}, this)" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    <textarea
                        id="inst_narration_input_{{ $installment->id }}"
                        rows="2"
                        data-original="{{ e($installment->narration ?? '') }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveNarration({{ $installment->id }}, this)">{{ $installment->narration ?? '' }}</textarea>
                </td>
                <td>
                    <span id="inst_amount_display_{{ $installment->id }}">{{ number_format($installment->amount, 2) }}</span>
                    @if($canInlineEdit)
                    <a href="javascript:void(0);" onclick="editAmount({{ $installment->id }}, this)" class="ms-2">
                        <i class="fa fa-edit text-primary"></i>
                    </a>
                    @endif
                    <input type="number"
                        step="0.01"
                        id="inst_amount_input_{{ $installment->id }}"
                        value="{{ $installment->amount }}"
                        class="form-control form-control-sm d-none"
                        onblur="saveAmount({{ $installment->id }}, this)"
                        onkeypress="if(event.keyCode==13) saveAmount({{ $installment->id }}, this)">
                </td>
                <td>
                    <span id="inst_created_by_display_{{ $installment->id }}">{{ $installment->created_by ? \App\Models\User::find($installment->created_by)->name :''}}</span>
                </td>
                <td>
                    <span id="inst_updated_by_display_{{ $installment->id }}">{{ $installment->updated_by ? \App\Models\User::find($installment->updated_by)->name :''}}</span>
                </td>
                <td>{!! $installment->status_badge !!}</td>
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
                            @if($canEditInstallment)
                            {{-- Payment Receiving --}}
                            @if($installment->status !== 'paid')
                            <a href="javascript:void(0);"
                                class="dropdown-item waves-effect show-modal action-btn"
                                data-action="{{ route($installmentPaymentReceivingRoute, $installment->rider_id) }}"
                                data-size="xl"
                                data-title="Payment Receiving — {{ optional($installment->expenseAccount)->name ?? 'Installments' }}">
                                <i class="fa fa-money-bill-wave me-2 text-primary"></i> Payment Receiving
                            </a>
                            <div class="dropdown-divider"></div>
                            @endif
                            @foreach($rowLinkedReceipts as $linkedReceipt)
                            @php
                              $linkedRvLabel = $linkedReceipt->voucher_id
                                ? 'RV-' . $linkedReceipt->voucher_id
                                : 'Receipt #' . $linkedReceipt->id;
                            @endphp
                            <a href="javascript:void(0);"
                                class="dropdown-item waves-effect show-modal action-btn"
                                data-action="{{ route($installmentEditPaymentReceivingRoute, $linkedReceipt->id) }}"
                                data-size="xl"
                                data-title="Edit {{ $linkedRvLabel }}">
                                <i class="fa fa-edit me-2"></i> Edit {{ $linkedRvLabel }}
                            </a>
                            <a href="javascript:void(0);"
                                class="dropdown-item waves-effect text-danger"
                                onclick='confirmDeleteProtected("{{ route('receipts.destroy', $linkedReceipt->id) }}")'>
                                <i class="fa fa-trash me-2"></i> Delete {{ $linkedRvLabel }}
                            </a>
                            @endforeach
                            @if($rowLinkedReceipts !== [])
                            <div class="dropdown-divider"></div>
                            @endif
                            @if($installment->status === 'pending' || $installment->status === 'partial')
                            <a href="javascript:void(0);"
                                onclick="markAsPaid({{ $installment->id }})"
                                class='dropdown-item waves-effect'>
                                <i class="fa fa-check me-2"></i> Mark as Paid
                            </a>
                            @elseif(!$hasLinkedReceipts)
                            <a href="javascript:void(0);"
                                onclick="markAsPending({{ $installment->id }})"
                                class='dropdown-item waves-effect'>
                                <i class="fa fa-undo me-2"></i> Mark as Pending
                            </a>
                            @endif
                            @endif
                            @if($canDeleteInstallment)
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0);"
                                onclick='confirmDeleteProtected("{{ route($installmentDeleteRoute, ['id' => $installment->id]) }}")'
                                class='dropdown-item waves-effect text-danger'>
                                <i class="fa fa-trash me-2"></i> Delete
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="rl-empty text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i>
                    No installment plans found. <br>
                    <small>Click "Create Installment Plan" to get started.</small>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if(method_exists($data, 'links'))
    {!! $data->links('pagination') !!}
    @endif
</div>

<script>
    // Scope id is dynamic (e.g. visa-installment-plan-inline-edit-scope). A previous
    // hardcoded "visa-installments-..." id made pencil edits silently no-op.
    function installmentInlineScope(fromEl) {
        if (fromEl && fromEl.closest) {
            const nearest = fromEl.closest('[id$="-inline-edit-scope"]');
            if (nearest) {
                return nearest;
            }
        }
        return document.getElementById(@json($installmentTableScope . '-inline-edit-scope'));
    }

    function installmentFieldEl(field, installmentId, fromEl) {
        const id = 'inst_' + field + '_' + installmentId;
        const scope = installmentInlineScope(fromEl);
        if (scope) {
            const scoped = scope.querySelector('#' + id);
            if (scoped) {
                return scoped;
            }
        }
        return document.getElementById(id);
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

                submitForm('{{ route($installmentPayRoute) }}', {
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

                submitForm('{{ route($installmentPayRoute) }}', {
                    'installment_id': installmentId,
                    'status': 'pending'
                });
            }
        });
    }

    // Edit functions - show input field
    function editDate(installmentId, fromEl) {
        const dateDisplay = installmentFieldEl('date_display', installmentId, fromEl);
        const dateInput = installmentFieldEl('date_input', installmentId, fromEl);
        if (!dateDisplay || !dateInput) return;
        dateDisplay.classList.add('d-none');
        dateInput.classList.remove('d-none');
        dateInput.focus();
    }

    function editBillingMonth(installmentId, fromEl) {
        const billingDisplay = installmentFieldEl('billing_display', installmentId, fromEl);
        const billingInput = installmentFieldEl('billing_input', installmentId, fromEl);
        if (!billingDisplay || !billingInput) return;
        billingDisplay.classList.add('d-none');
        billingInput.classList.remove('d-none');
        billingInput.focus();
    }

    function editAmount(installmentId, fromEl) {
        const amountDisplay = installmentFieldEl('amount_display', installmentId, fromEl);
        const amountInput = installmentFieldEl('amount_input', installmentId, fromEl);
        if (!amountDisplay || !amountInput) return;
        amountDisplay.classList.add('d-none');
        amountInput.classList.remove('d-none');
        amountInput.focus();
        amountInput.select();
    }

    function editNarration(installmentId, fromEl) {
        const narrationDisplay = installmentFieldEl('narration_display', installmentId, fromEl);
        const narrationInput = installmentFieldEl('narration_input', installmentId, fromEl);
        if (!narrationDisplay || !narrationInput) return;
        narrationDisplay.classList.add('d-none');
        narrationInput.classList.remove('d-none');
        narrationInput.focus();
        narrationInput.select();
    }

    // Save functions - hide input and save data
    function saveDate(installmentId, fromEl) {
        const dateInput = installmentFieldEl('date_input', installmentId, fromEl);
        const dateDisplay = installmentFieldEl('date_display', installmentId, fromEl);
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
                        submitForm('{{ route($installmentUpdateFieldRoute) }}', {
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

    function saveBillingMonth(installmentId, fromEl) {
        const billingInput = installmentFieldEl('billing_input', installmentId, fromEl);
        const billingDisplay = installmentFieldEl('billing_display', installmentId, fromEl);
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
                    submitForm('{{ route($installmentUpdateFieldRoute) }}', {
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

    function saveAmount(installmentId, fromEl) {
        const amountInput = installmentFieldEl('amount_input', installmentId, fromEl);
        const amountDisplay = installmentFieldEl('amount_display', installmentId, fromEl);
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
                    submitForm('{{ route($installmentUpdateFieldRoute) }}', {
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

    function saveNarration(installmentId, fromEl) {
        const narrationInput = installmentFieldEl('narration_input', installmentId, fromEl);
        const narrationDisplay = installmentFieldEl('narration_display', installmentId, fromEl);
        if (!narrationInput || !narrationDisplay) return;
        const newValue = narrationInput.value.trim();
        const originalValue = (narrationInput.getAttribute('data-original') || narrationInput.defaultValue || '').trim();

        if (newValue === originalValue) {
            narrationInput.classList.add('d-none');
            narrationDisplay.classList.remove('d-none');
            return;
        }

        submitForm('{{ route($installmentUpdateFieldRoute) }}', {
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

    // Soft delete with cascade tracking confirmation — provided by shared partial below
</script>
@include('delete_requests._confirm_delete_script', [
'entityName' => 'Installment Plan',
'confirmText' => 'This will submit a delete request for the installment plan (and related vouchers/transactions). Until approved, records stay visible and locked.',
'method' => 'GET',
'functionName' => 'confirmDeleteProtected',
])
@once
@include('visa_expenses.partials.multi_delete_scripts')
@endonce
<script>
    // Store original values when page loads (capture this table's scope id in the listener).
    document.addEventListener('DOMContentLoaded', function() {
        const scope = document.getElementById(@json($installmentTableScope . '-inline-edit-scope'));
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