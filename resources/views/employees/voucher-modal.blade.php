@php
$actions = [
'PN' => route('employees.storepenalty'),
'INC' => route('employees.storeincentive'),
'AL' => route('employees.storeadvanceloan'),
];

$editActions = [];

$urls = [
'PN' => route('employees.penalty', ['id' => $employee->id ?? 0]),
'INC' => route('employees.incentive', ['id' => $employee->id ?? 0]),
'AL' => route('employees.advanceloan', ['id' => $employee->id ?? 0]),
'PAY' => route('payments.create', ['invoice_type' => 'employee', 'employee_id' => $employee->id ?? 0]),
];

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
    <input type="hidden" id="reload_page" value="1">
    <input type="hidden" id="employee_id" value="{{ $employee->id ?? '' }}">
    <input type="hidden" id="employee_branch_id" value="{{ $employee->branch_id ?? '' }}">
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

<script>
    (function() {
        const container = document.getElementById('voucherFormContainer');
        const actionsB64 = container.getAttribute('data-actions-b64') || '';
        const editActionsB64 = container.getAttribute('data-edit-actions-b64') || '';
        const urlsB64 = container.getAttribute('data-urls-b64') || '';
        const typeToAction = actionsB64 ? JSON.parse(atob(actionsB64)) : {};
        const typeToEditAction = editActionsB64 ? JSON.parse(atob(editActionsB64)) : {};
        const typeToUrl = urlsB64 ? JSON.parse(atob(urlsB64)) : {};

        const employeeBranchId = document.getElementById('employee_branch_id').value;
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

        function initLoadedForm(form, temp) {
            if (!form.querySelector('input[name="_token"]')) {
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.prepend(csrf);
            }

            let branchInput = form.querySelector('input[name="branch_id"]');
            if (!branchInput) {
                branchInput = document.createElement('input');
                branchInput.type = 'hidden';
                branchInput.name = 'branch_id';
                form.prepend(branchInput);
            }
            // Prefer employee branch; keep existing value only when employee has none
            if (employeeBranchId) {
                branchInput.value = employeeBranchId;
            } else if (!branchInput.value) {
                branchInput.value = '';
            }

            $(temp).find('script').each(function() {
                $.globalEval(this.text || this.textContent || this.innerHTML || '');
            });

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
        }

        function loadPaymentVoucherForm(url) {
            container.innerHTML = '<div class="text-center p-4 text-muted">Loading payment form...</div>';

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    redirect: 'follow'
                })
                .then(async (r) => {
                    const body = await r.text();
                    if (!r.ok) {
                        let message = 'Failed to load payment form.';
                        if (body.indexOf('modal-load-error') !== -1) {
                            container.innerHTML = body;
                            const errText = container.querySelector('.modal-load-error p');
                            if (errText && errText.textContent) {
                                message = errText.textContent.trim();
                            }
                        } else {
                            container.innerHTML = '<div class="alert alert-danger modal-load-error"><p>' + message + '</p></div>';
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error(message);
                        }
                        return;
                    }

                    const temp = document.createElement('div');
                    temp.innerHTML = body;
                    const originalForm = temp.querySelector('form#formajax') || temp.querySelector('form');

                    if (!originalForm) {
                        container.innerHTML = '<div class="alert alert-danger">Payment form could not be loaded.</div>';
                        return;
                    }

                    container.innerHTML = '';
                    container.appendChild(originalForm);
                    initLoadedForm(originalForm, temp);

                    $('#modalTopTitle').text('Record Employee Payment');
                })
                .catch(() => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load payment form.</div>';
                });
        }

        function loadFormFor(type, isEdit = false) {
            if (!type) {
                container.innerHTML = '';
                return;
            }

            const url = typeToUrl[type] || '';
            if (!url) {
                container.innerHTML = '';
                return;
            }

            if (type === 'PAY' && !isEdit) {
                loadPaymentVoucherForm(url);
                return;
            }

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
                    const action = isEdit ? (typeToEditAction[type] || '#') : (typeToAction[type] || '#');

                    container.innerHTML = '<form id="formajax" method="post" action="' + action + '"></form>';
                    const form = container.querySelector('#formajax');
                    form.innerHTML = inner;

                    if (isEdit) {
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'PUT';
                        form.prepend(methodField);
                    }

                    initLoadedForm(form, temp);
                })
                .catch(() => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
                });
        }

        $typeSelect.on('change', function() {
            loadFormFor(this.value, false);
        });

        if (editMode && voucherType) {
            loadFormFor(voucherType, true);
        }
    })();
</script>
