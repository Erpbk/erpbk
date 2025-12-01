<?php
// Map voucher type to form action route
$actions = [
'AL' => route('riders.storeadvanceloan'),
'COD' => route('riders.storecod'),
'PN' => route('riders.storepenalty'),
'PAY' => route('riders.storepayment'),
'VC' => route('riders.storevendorcharges'),
];

// Map voucher type to edit action route if voucher_id is provided
$editActions = [];
if (isset($voucher_id)) {
$editActions = [
'AL' => route('riders.editadvanceloan', ['id' => $voucher_id]),
'COD' => route('riders.editcod', ['id' => $voucher_id]),
'PN' => route('riders.editpenalty', ['id' => $voucher_id]),
'PAY' => route('riders.editpayment', ['id' => $voucher_id]),
'VC' => route('riders.editvendorcharges', ['id' => $voucher_id]),
];
}

// Map voucher type to field-rendering endpoint (with rider id)
$urls = [
'AL' => route('riders.advanceloan', ['id' => $rider->id ?? 0]),
'COD' => route('riders.cod', ['id' => $rider->id ?? 0]),
'PN' => route('riders.penalty', ['id' => $rider->id ?? 0]),
'PAY' => route('riders.payment', ['id' => $rider->id ?? 0]),
'VC' => route('riders.vendorcharges', ['id' => $rider->id ?? 0]),
];

// If editing, get the voucher type from the voucher
$editMode = isset($voucher_id) && isset($voucher_type);
?>

<div class="mb-3">
    <label class="form-label">Voucher Type</label>
    <select id="voucherType" class="form-select form-select-sm" <?php echo e($editMode ? 'disabled' : ''); ?>>
        <option value="">Select</option>
        <?php $__currentLoopData = $voucherTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($code); ?>" <?php echo e(isset($voucher_type) && $voucher_type == $code ? 'selected' : ''); ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <small class="text-muted">Incentive is separate and not included here.</small>
    <input type="hidden" id="reload_page" value="1">
    <input type="hidden" id="rider_id" value="<?php echo e($rider->id ?? ''); ?>">
    <input type="hidden" id="voucher_id" value="<?php echo e($voucher_id ?? ''); ?>">
    <input type="hidden" id="voucher_type" value="<?php echo e($voucher_type ?? ''); ?>">
    <input type="hidden" id="edit_mode" value="<?php echo e($editMode ? '1' : '0'); ?>">
    <input type="hidden" id="base_url" value="<?php echo e(url('/')); ?>">
</div>

<div id="voucherFormContainer"
    data-actions-b64="<?php echo e(base64_encode(json_encode($actions))); ?>"
    data-edit-actions-b64="<?php echo e(base64_encode(json_encode($editActions))); ?>"
    data-urls-b64="<?php echo e(base64_encode(json_encode($urls))); ?>">
</div>

<!-- Templates removed; forms will be loaded via AJAX when a type is selected. -->

<script>
    (function() {
        const container = document.getElementById('voucherFormContainer');
        const actionsB64 = container.getAttribute('data-actions-b64') || '';
        const editActionsB64 = container.getAttribute('data-edit-actions-b64') || '';
        const urlsB64 = container.getAttribute('data-urls-b64') || '';
        const typeToAction = actionsB64 ? JSON.parse(atob(actionsB64)) : {};
        const typeToEditAction = editActionsB64 ? JSON.parse(atob(editActionsB64)) : {};
        const typeToUrl = urlsB64 ? JSON.parse(atob(urlsB64)) : {};

        const riderId = document.getElementById('rider_id').value;
        const voucherId = document.getElementById('voucher_id').value;
        const voucherType = document.getElementById('voucher_type').value;
        const editMode = document.getElementById('edit_mode').value === '1';
        const typeSelect = document.getElementById('voucherType');

        function loadFormFor(type, isEdit = false) {
            if (!type) {
                container.innerHTML = '';
                return;
            }

            // Load the specific form via AJAX from existing endpoints
            const url = typeToUrl[type] || '';
            if (!url) {
                container.innerHTML = '';
                return;
            }

            // Determine if we're in edit mode and have a voucher ID
            const fetchUrl = isEdit ? `${url}&voucher_id=${voucherId}` : url;

            fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;

                    const originalForm = temp.querySelector('form#formajax') || temp.querySelector('form');
                    const inner = originalForm ? originalForm.innerHTML : temp.innerHTML;

                    // Choose the appropriate action based on edit mode
                    const action = isEdit ? (typeToEditAction[type] || '#') : (typeToAction[type] || '#');

                    // Set up the form with the appropriate action
                    container.innerHTML = '<form id="formajax" method="post" action="' + action + '"></form>';
                    const form = container.querySelector('#formajax');
                    form.innerHTML = inner;

                    // Add CSRF token
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '<?php echo e(csrf_token()); ?>';
                    form.prepend(csrf);

                    // If in edit mode, add method PUT
                    if (isEdit) {
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'PUT';
                        form.prepend(methodField);
                    }

                    if (typeof window.getTotal === 'function') {
                        window.getTotal();
                    }
                })
                .catch(() => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
                });
        }

        typeSelect.addEventListener('change', function() {
            loadFormFor(this.value, false);
        });

        // If in edit mode, automatically load the form with the selected voucher type
        if (editMode && voucherType) {
            loadFormFor(voucherType, true);
        }
    })();
</script><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/riders/voucher-modal.blade.php ENDPATH**/ ?>