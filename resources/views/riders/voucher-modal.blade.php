@php
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
@endphp

<div class="mb-3">
    <label class="form-label">Voucher Type</label>
    <select id="voucherType" class="form-select form-select-sm select2" {{ $editMode ? 'disabled' : '' }}>
        <option value="">Select</option>
        @foreach($voucherTypes as $code => $label)
        <option value="{{ $code }}" {{ isset($voucher_type) && $voucher_type == $code ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <small class="text-muted">Incentive is separate and not included here.</small>
    <input type="hidden" id="reload_page" value="1">
    <input type="hidden" id="rider_id" value="{{ $rider->id ?? '' }}">
    <input type="hidden" id="voucher_id" value="{{ $voucher_id ?? '' }}">
    <input type="hidden" id="voucher_type" value="{{ $voucher_type ?? '' }}">
    <input type="hidden" id="edit_mode" value="{{ $editMode ? '1' : '0' }}">
    <input type="hidden" id="base_url" value="{{ url('/') }}">
</div>

<div id="voucherFormContainer"
    data-actions-b64="{{ base64_encode(json_encode($actions)) }}"
    data-edit-actions-b64="{{ base64_encode(json_encode($editActions)) }}"
    data-urls-b64="{{ base64_encode(json_encode($urls)) }}">
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
        const $typeSelect = $(typeSelect);

        if ($.fn.select2 && !$typeSelect.hasClass('select2-hidden-accessible')) {
            $typeSelect.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select',
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
            });
        }

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
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                })
                .then(async (r) => {
                    const body = await r.text();
                    if (!r.ok) {
                        let message = 'Failed to load form.';

                        if (body.indexOf('modal-load-error') !== -1) {
                            container.innerHTML = body;
                            const errText = container.querySelector('.modal-load-error p');
                            if (errText && errText.textContent) {
                                message = errText.textContent.trim();
                            }
                        } else {
                            try {
                                const json = JSON.parse(body);
                                if (json && json.message) {
                                    message = json.message;
                                }
                            } catch (e) {
                                // keep default message
                            }
                            container.innerHTML = '<div class="alert alert-danger">' + message + '</div>';
                        }

                        if (typeof toastr !== 'undefined') {
                            toastr.error(message);
                        }
                        return;
                    }

                    const temp = document.createElement('div');
                    temp.innerHTML = body;

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
                    csrf.value = '{{ csrf_token() }}';
                    form.prepend(csrf);

                    // If in edit mode, add method PUT
                    if (isEdit) {
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'PUT';
                        form.prepend(methodField);
                    }

                    // innerHTML does not run <script> tags — execute them manually
                    $(temp).find('script').each(function() {
                        $.globalEval(this.text || this.textContent || this.innerHTML || '');
                    });

                    // Init select2 the same way as custom.js modal forms
                    if (window.jQuery && $.fn && $.fn.select2) {
                        $(form).find('select.select2').each(function() {
                            var $select = $(this);
                            if ($select.hasClass('select2-hidden-accessible')) {
                                return;
                            }
                            $select.select2({
                                width: '100%',
                                allowClear: true,
                                dropdownParent: $('#modalTopbody')
                            });
                        });
                    }

                    if (typeof window.getTotal === 'function') {
                        window.getTotal();
                    }
                })
                .catch(() => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
                });
        }

        $typeSelect.on('change', function() {
            loadFormFor(this.value, false);
        });

        // If in edit mode, automatically load the form with the selected voucher type
        if (editMode && voucherType) {
            loadFormFor(voucherType, true);
        }
    })();
</script>