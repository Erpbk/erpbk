<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
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
        <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $installment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="text-center" data-status="<?php echo e($installment->status); ?>">
            <td>
                <span id="date_display_<?php echo e($installment->id); ?>"><?php echo e(\Carbon\Carbon::parse($installment->date)->format('d M Y')); ?></span>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaloan_edit')): ?>
                <a href="javascript:void(0);" onclick="editDate(<?php echo e($installment->id); ?>)" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                <?php endif; ?>
                <input type="date"
                    id="date_input_<?php echo e($installment->id); ?>"
                    value="<?php echo e(\Carbon\Carbon::parse($installment->date)->format('Y-m-d')); ?>"
                    min="<?php echo e(\Carbon\Carbon::today()->format('Y-m-d')); ?>"
                    class="form-control form-control-sm d-none"
                    onblur="saveDate(<?php echo e($installment->id); ?>)"
                    onkeypress="if(event.keyCode==13) saveDate(<?php echo e($installment->id); ?>)">
            </td>
            <td>
                <span id="voucher_ids_display_<?php echo e($installment->id); ?>">
                    <?php if($installment->vouchers->isNotEmpty()): ?>
                    <?php $__currentLoopData = $installment->vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('vouchers.show', $voucher->id)); ?>" target="_blank"><?php echo e($voucher->formatted_id); ?></a><?php if(!$loop->last): ?>, <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                    <?php echo e($installment->voucher_ids); ?>

                    <?php endif; ?>
                </span>
            </td>
            <td>
                <span id="billing_display_<?php echo e($installment->id); ?>"><?php echo e(\Carbon\Carbon::parse($installment->billing_month)->format('M Y')); ?></span>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaloan_edit')): ?>
                <a href="javascript:void(0);" onclick="editBillingMonth(<?php echo e($installment->id); ?>)" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                <?php endif; ?>
                <input type="month"
                    id="billing_input_<?php echo e($installment->id); ?>"
                    value="<?php echo e(\Carbon\Carbon::parse($installment->billing_month)->format('Y-m')); ?>"
                    min="<?php echo e(\Carbon\Carbon::today()->format('Y-m')); ?>"
                    class="form-control form-control-sm d-none"
                    onblur="saveBillingMonth(<?php echo e($installment->id); ?>)"
                    onkeypress="if(event.keyCode==13) saveBillingMonth(<?php echo e($installment->id); ?>)">
            </td>
            <td>
                <span id="amount_display_<?php echo e($installment->id); ?>"><?php echo e(number_format($installment->amount, 2)); ?></span>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaloan_edit')): ?>
                <a href="javascript:void(0);" onclick="editAmount(<?php echo e($installment->id); ?>)" class="ms-2">
                    <i class="fa fa-edit text-primary"></i>
                </a>
                <?php endif; ?>
                <input type="number"
                    step="0.01"
                    id="amount_input_<?php echo e($installment->id); ?>"
                    value="<?php echo e($installment->amount); ?>"
                    class="form-control form-control-sm d-none"
                    onblur="saveAmount(<?php echo e($installment->id); ?>)"
                    onkeypress="if(event.keyCode==13) saveAmount(<?php echo e($installment->id); ?>)">
            </td>
            <td><?php echo $installment->status_badge; ?></td>
            <td>
                <span id="created_by_display_<?php echo e($installment->id); ?>"><?php echo e($installment->created_by ? \App\Models\User::find($installment->created_by)->name :''); ?></span>
            </td>
            <td>
                <span id="updated_by_display_<?php echo e($installment->id); ?>"><?php echo e($installment->updated_by ? \App\Models\User::find($installment->updated_by)->name :''); ?></span>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown<?php echo e($installment->id); ?>" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown<?php echo e($installment->id); ?>">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaloan_edit')): ?>
                        <?php if($installment->status === 'pending'): ?>
                        <a href="javascript:void(0);"
                            onclick="markAsPaid(<?php echo e($installment->id); ?>)"
                            class='dropdown-item waves-effect'>
                            <i class="fa fa-check me-2"></i> Mark as Paid
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="javascript:void(0);"
                            onclick='confirmDeleteProtected("<?php echo e(route('VisaExpense.deleteInstallment', $installment->id)); ?>")'
                            class='dropdown-item waves-effect text-danger'>
                            <i class="fa fa-trash me-2"></i> Delete
                        </a>
                        <?php else: ?>
                        <a href="javascript:void(0);"
                            onclick="markAsPending(<?php echo e($installment->id); ?>)"
                            class='dropdown-item waves-effect'>
                            <i class="fa fa-undo me-2"></i> Mark as Pending
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="dropdown-item-text text-<?php echo e($installment->status === 'paid' ? 'success' : 'warning'); ?>">
                            <i class="fa fa-<?php echo e($installment->status === 'paid' ? 'check' : 'clock'); ?> me-2"></i> <?php echo e(ucfirst($installment->status)); ?>

                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="7" class="text-center text-muted py-4">
                <i class="fa fa-info-circle me-2"></i>
                No installment plans found. <br>
                <small>Click "Create Installment Plan" to get started.</small>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
    <?php if($data->count() > 0): ?>
    <tfoot>
        <tr class="bg-light">
            <td colspan="2" class="text-end"><strong>Total Amount Reference:</strong></td>
            <td class="text-center">
                <strong>
                    <?php
                    $totalAmount = $data->first()->total_amount ?? 0;
                    $currentTotal = $data->sum('amount');
                    $riderId = $data->first()->rider_id ?? null;
                    $paidTotal = $riderId ? \App\Models\visa_installment_plan::where('rider_id', $riderId)->where('status', 'paid')->sum('amount') : 0;
                    $pendingTotalAll = $riderId ? \App\Models\visa_installment_plan::where('rider_id', $riderId)->where('status', 'pending')->sum('amount') : 0;
                    ?>
                    <span id="total-amount-reference"><?php echo e(number_format($totalAmount, 2)); ?></span>
                    <br>
                    <small id="current-total-amount-container" class="text-warning">(Current: <span><?php echo e(number_format($currentTotal, 2)); ?></span>)</small>
                </strong>
            </td>
            <td colspan="5"></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
<?php echo $data->links('pagination'); ?>


<script>
    // Track unsaved amount changes locally until user finalizes
    let INSTALLMENT_AMOUNT_CHANGES = {};
    let INSTALLMENT_DELETIONS = {};
    let INSTALLMENT_ADDITIONS = [];
    let IS_FINALIZING = false;

    function markAsPaid(installmentId) {
        if (confirm('Are you sure you want to mark this installment as paid?')) {
            submitForm('<?php echo e(route("VisaExpense.payInstallment")); ?>', {
                'installment_id': installmentId
            });
        }
    }

    function markAsPending(installmentId) {
        if (confirm('Are you sure you want to mark this installment as pending?')) {
            submitForm('<?php echo e(route("VisaExpense.payInstallment")); ?>', {
                'installment_id': installmentId,
                'status': 'pending'
            });
        }
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
                    alert('You cannot select a date in the past for pending installments.');
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
                showFinalizeBannerAttention();
                updateFinalizeBannerVisibility();
                return;
            } else {
                // For pending installments, confirm and submit directly
                if (confirm('Are you sure you want to update the date? This will also update subsequent installments, voucher and transactions.')) {
                    submitForm('<?php echo e(route("VisaExpense.updateInstallmentField")); ?>', {
                        'installment_id': installmentId,
                        'field': 'date',
                        'value': newValue,
                        'update_subsequent': true
                    });
                    return;
                }
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
                    alert('You cannot select a billing month in the past for pending installments.');
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
                showFinalizeBannerAttention();
                updateFinalizeBannerVisibility();
                return;
            } else {
                // For pending installments, confirm and submit directly
                if (confirm('Are you sure you want to update the billing month? This will also update subsequent installments, voucher and transactions.')) {
                    submitForm('<?php echo e(route("VisaExpense.updateInstallmentField")); ?>', {
                        'installment_id': installmentId,
                        'field': 'billing_month',
                        'value': newValue,
                        'update_subsequent': true
                    });
                    return;
                }
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
            alert('Amount must be greater than 0.');
            amountInput.value = originalValue;
            validateAmountInput(amountInput);
        } else {
            // Update display immediately for all installments (paid or pending)
            amountDisplay.textContent = formatCurrency(parseFloat(newValue));

            // Track change if different from original; else remove from tracking
            if (newValue !== originalValue) {
                INSTALLMENT_AMOUNT_CHANGES[installmentId] = parseFloat(newValue);
                amountInput.setAttribute('data-changed', '1');

                // For paid installments, mark the row to indicate it needs finalization
                if (isPaid) {
                    row.classList.add('bg-warning-subtle');
                    row.setAttribute('data-modified', '1');
                    showFinalizeBannerAttention();
                }
            } else {
                delete INSTALLMENT_AMOUNT_CHANGES[installmentId];
                amountInput.removeAttribute('data-changed');

                // Remove highlight if reverting to original value
                if (isPaid) {
                    row.classList.remove('bg-warning-subtle');
                    row.removeAttribute('data-modified');
                }
            }
        }

        // Hide input, show display
        amountInput.classList.add('d-none');
        amountDisplay.classList.remove('d-none');

        // Update indicators
        updateInstallmentDifferenceBadge();
        updateFinalizeBannerVisibility();
    }

    function submitForm(action, data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '<?php echo e(csrf_token()); ?>';
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
                alert('Unable to detect installment to delete.');
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

            alert('First finalize the payment, otherwise it will not redirect to any module.');
            updateInstallmentDifferenceBadge();
            updateFinalizeBannerVisibility();
            showFinalizeBannerAttention();
            return false;
        } catch (e) {
            alert('First finalize the payment, otherwise it will not redirect to any module.');
            showFinalizeBannerAttention();
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
                updateInstallmentDifferenceBadge();
            });
        });

        // Initial badge state on load
        updateInstallmentDifferenceBadge();
        updateFinalizeBannerVisibility();
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

        // Live tracking while typing (do not submit)
        const originalVal = input.getAttribute('data-original');
        const installmentId = input.id.replace('amount_input_', '');
        if (!isNaN(amount) && amount > 0 && originalVal !== null) {
            if (parseFloat(originalVal) !== amount) {
                INSTALLMENT_AMOUNT_CHANGES[installmentId] = amount;
                input.setAttribute('data-changed', '1');
            } else {
                delete INSTALLMENT_AMOUNT_CHANGES[installmentId];
                input.removeAttribute('data-changed');
            }
        }

        updateFinalizeBannerVisibility();
    }

    // Difference badge logic
    const MAIN_TOTAL_AMOUNT = parseFloat('<?php echo e($data->first()->total_amount ?? 0); ?>');
    const PAID_TOTAL_AMOUNT = parseFloat('<?php echo e($paidTotal ?? 0); ?>');
    const PENDING_TOTAL_ALL = parseFloat('<?php echo e($pendingTotalAll ?? 0); ?>');

    function getPendingInstallmentsSumFromInputs() {
        let sum = 0;
        const pendingRows = document.querySelectorAll('tr[data-status="pending"]');
        pendingRows.forEach(row => {
            if (row.getAttribute('data-deleted') === '1') return;
            const amountInput = row.querySelector('[id^="amount_input_"]');
            if (amountInput) {
                const val = parseFloat(amountInput.value);
                if (!isNaN(val)) {
                    sum += val;
                }
            }
        });
        return sum;
    }

    function getVisiblePendingDelta() {
        let delta = 0;

        // Process pending rows
        const pendingRows = document.querySelectorAll('tr[data-status="pending"]');
        pendingRows.forEach(row => {
            if (row.getAttribute('data-deleted') === '1') return;
            const amountInput = row.querySelector('[id^="amount_input_"]');
            if (amountInput) {
                const currentVal = parseFloat(amountInput.value);
                const originalVal = parseFloat(amountInput.getAttribute('data-original'));
                const safeCurrent = isNaN(currentVal) ? 0 : currentVal;
                const safeOriginal = isNaN(originalVal) ? 0 : originalVal;
                delta += (safeCurrent - safeOriginal);
            }
        });

        // include paid rows that have been modified
        const paidRows = document.querySelectorAll('tr[data-status="paid"][data-modified="1"]');
        paidRows.forEach(row => {
            const amountInput = row.querySelector('[id^="amount_input_"]');
            if (amountInput) {
                const currentVal = parseFloat(amountInput.value);
                const originalVal = parseFloat(amountInput.getAttribute('data-original'));
                const safeCurrent = isNaN(currentVal) ? 0 : currentVal;
                const safeOriginal = isNaN(originalVal) ? 0 : originalVal;
                // For paid rows, we need to add the difference to the pending total
                delta += (safeCurrent - safeOriginal);
            }
        });

        // include deletions delta (subtract original amounts)
        for (const [id, originalAmount] of Object.entries(INSTALLMENT_DELETIONS)) {
            const orig = parseFloat(originalAmount);
            if (!isNaN(orig)) delta += -orig;
        }
        // include additions delta (add new amounts)
        if (Array.isArray(INSTALLMENT_ADDITIONS) && INSTALLMENT_ADDITIONS.length > 0) {
            for (const add of INSTALLMENT_ADDITIONS) {
                const val = parseFloat(add.amount);
                if (!isNaN(val)) delta += val;
            }
        }
        return delta;
    }

    function updateInstallmentDifferenceBadge() {
        const badge = document.getElementById('installment-diff-badge');
        if (!badge) return;

        // Start with server-side sum of all pending installments (across pages)
        // Then adjust by delta from any visible edits not yet saved
        const pendingLive = PENDING_TOTAL_ALL + getVisiblePendingDelta();
        const combined = PAID_TOTAL_AMOUNT + pendingLive;
        const diff = Math.abs(MAIN_TOTAL_AMOUNT - combined);
        if (diff > 0.009) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }

        // Update the bottom "Current" total in real-time
        const currentEl = document.getElementById('current-total-amount');
        if (currentEl) {
            const visibleCurrentSum = getPendingInstallmentsSumFromInputs();
            const additionsSum = Array.isArray(INSTALLMENT_ADDITIONS) ? INSTALLMENT_ADDITIONS.reduce((s, a) => s + (parseFloat(a.amount) || 0), 0) : 0;
            const deletionsSum = Object.values(INSTALLMENT_DELETIONS).reduce((s, v) => s + (parseFloat(v) || 0), 0);
            currentEl.textContent = formatCurrency(visibleCurrentSum + additionsSum - deletionsSum);
        }
    }

    function computeAmountsDiff() {
        const pendingLive = PENDING_TOTAL_ALL + getVisiblePendingDelta();
        const combined = PAID_TOTAL_AMOUNT + pendingLive;
        return Math.abs(MAIN_TOTAL_AMOUNT - combined);
    }

    function isTotalValid() {
        return computeAmountsDiff() <= 0.009;
    }

    function hasUnsavedChanges() {
        return Object.keys(INSTALLMENT_AMOUNT_CHANGES).length > 0 ||
            Object.keys(INSTALLMENT_DELETIONS).length > 0 ||
            (Array.isArray(INSTALLMENT_ADDITIONS) && INSTALLMENT_ADDITIONS.length > 0) ||
            (window.DATE_CHANGES && Object.keys(window.DATE_CHANGES).length > 0) ||
            (window.BILLING_CHANGES && Object.keys(window.BILLING_CHANGES).length > 0);
    }

    function updateFinalizeBannerVisibility() {
        const banner = document.getElementById('finalize-payment-banner');
        const btn = document.getElementById('finalize-payment-btn');
        const warn = document.getElementById('finalize-payment-warn');
        if (!banner) return;
        if (hasUnsavedChanges()) {
            banner.classList.remove('d-none');
            if (btn) {
                const valid = isTotalValid();
                btn.disabled = !valid;
                btn.classList.toggle('btn-secondary', !valid);
                btn.classList.toggle('btn-primary', valid);
                btn.title = valid ? '' : 'Totals mismatch. Adjust amounts to match the required total.';
            }
            if (warn) {
                warn.classList.toggle('d-none', isTotalValid());
            }
        } else {
            banner.classList.add('d-none');
            if (btn) btn.disabled = true;
        }
    }

    function finalizePayment() {
        if (!hasUnsavedChanges()) {
            alert('No changes to finalize.');
            return;
        }
        if (!isTotalValid()) {
            alert('Totals mismatch. Adjust amounts to exactly match the required total before finalizing.');
            return;
        }
        // Build payload of changes + deletions + additions + date changes + billing changes
        const payload = {
            changes: JSON.stringify(INSTALLMENT_AMOUNT_CHANGES),
            deletions: JSON.stringify(Object.keys(INSTALLMENT_DELETIONS)),
            additions: JSON.stringify(INSTALLMENT_ADDITIONS),
            date_changes: JSON.stringify(window.DATE_CHANGES || {}),
            billing_changes: JSON.stringify(window.BILLING_CHANGES || {})
        };
        IS_FINALIZING = true;
        submitForm('<?php echo e(route("VisaExpense.finalizePayment")); ?>', payload);
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

    // Prevent leaving the module with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if ((hasUnsavedChanges() || !isTotalValid()) && !IS_FINALIZING) {
            e.preventDefault();
            e.returnValue = 'First finalize the payment.';
            return 'First finalize the payment.';
        }
    });

    // Intercept in-page link clicks and form submissions
    document.addEventListener('click', function(e) {
        if (IS_FINALIZING) return;
        if (!hasUnsavedChanges() && isTotalValid()) return;
        const link = e.target.closest('a');
        if (link && link.getAttribute('href') && !link.getAttribute('href').startsWith('javascript')) {
            e.preventDefault();
            alert('First finalize the payment, otherwise it will not redirect to any module.');
        }
    });

    document.addEventListener('submit', function(e) {
        if (IS_FINALIZING) return;
        if (!hasUnsavedChanges() && isTotalValid()) return;
        e.preventDefault();
        alert('First finalize the payment, otherwise it will not redirect to any module.');
    }, true);

    // Helper: draw a temporary attention border on the finalize banner
    function showFinalizeBannerAttention() {
        const banner = document.getElementById('finalize-payment-banner');
        if (!banner) return;
        banner.classList.remove('d-none');
        try {
            banner.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        } catch (e) {}
        banner.classList.add('border', 'border-danger');
        setTimeout(() => {
            banner.classList.remove('border', 'border-danger');
        }, 2000);
    }

    // Public API to add a new installment (client-side only; requires finalize)
    function addNewInstallment(billingMonth, date, amount) {
        const val = parseFloat(amount);
        if (isNaN(val) || val <= 0) {
            alert('Invalid amount for new installment.');
            return;
        }
        INSTALLMENT_ADDITIONS.push({
            billing_month: billingMonth,
            date: date,
            amount: val
        });
        updateInstallmentDifferenceBadge();
        updateFinalizeBannerVisibility();
        showFinalizeBannerAttention();
    }
</script>

<div id="installment-diff-badge" class="badge bg-warning text-dark shadow d-none" style="position: fixed; bottom: 20px; right: 20px; z-index: 1060;">
    Installment amounts have a difference
    <span class="ms-2"><i class="fa fa-exclamation-triangle"></i></span>
    <span class="ms-2 small">Pending sum must equal total</span>
</div>

<div id="finalize-payment-banner" class="alert alert-warning border-0 shadow d-none" style="position: fixed; bottom: 70px; right: 20px; z-index: 1060;">
    <div class="d-flex align-items-center">
        <div>
            <strong>Unsaved changes:</strong> Amounts changed. First finalize the payment.
        </div>
        <button id="finalize-payment-btn" type="button" class="btn btn-sm btn-primary ms-3" onclick="finalizePayment()">
            <i class="fa fa-save me-1"></i> Finalize Payment
        </button>
    </div>
    <div id="finalize-payment-warn" class="text-danger small mt-1 d-none">Totals mismatch. Adjust amounts to match the required total.</div>
    <div class="small text-muted mt-1">Leaving this page is blocked until you finalize.</div>



</div>

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
                <form id="filterForm" action="<?php echo e(route('banks.index')); ?>" method="GET">
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
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/visa_expenses/installmentPlanTable.blade.php ENDPATH**/ ?>