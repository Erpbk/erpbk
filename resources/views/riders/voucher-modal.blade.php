@php
$requireRiderSelect = $requireRiderSelect ?? false;
$preselectedType = $preselectedType ?? '';
$riderOptions = $riderOptions ?? collect();
$riderId = (int) ($rider?->id ?? 0);

// Map voucher type to form action route
$actions = [
    'PN' => route('riders.storepenalty'),
    'INC' => route('riders.storeincentive'),
    'AL' => route('riders.storeadvanceloan'),
    'COD' => route('riders.storecod'),
    'VC' => route('riders.storevendorcharges'),
];

// Map voucher type to edit action route if voucher_id is provided
$editActions = [];
if (isset($voucher_id)) {
    $editActions = [
        'PN' => route('riders.editpenalty', ['id' => $voucher_id]),
        'AL' => route('riders.editadvanceloan', ['id' => $voucher_id]),
        'COD' => route('riders.editcod', ['id' => $voucher_id]),
        'VC' => route('riders.editvendorcharges', ['id' => $voucher_id]),
    ];
}

// URL templates use a sentinel so JS can swap in the selected rider id
$urlRid = $riderId > 0 ? $riderId : 999999001;
$urls = [
    'PN' => route('riders.penalty', ['id' => $urlRid]),
    'INC' => route('riders.incentive', ['id' => $urlRid]),
    'AL' => route('riders.advanceloan', ['id' => $urlRid]),
    'COD' => route('riders.cod', ['id' => $urlRid]),
    'VC' => route('riders.vendorcharges', ['id' => $urlRid]),
    'PAY' => route('payments.create', ['invoice_type' => 'rider', 'rider_id' => $urlRid]),
];
if ($riderId <= 0) {
    foreach ($urls as $code => $url) {
        $urls[$code] = str_replace((string) $urlRid, '__RID__', $url);
    }
}

$editMode = isset($voucher_id) && isset($voucher_type);
$lockVoucherType = $preselectedType !== '' && ! $editMode;
@endphp

@if($requireRiderSelect)
<div class="mb-3">
    <label class="form-label">Rider</label>
    <select id="voucherRiderSelect" class="form-select form-select-sm select2" {{ $editMode ? 'disabled' : '' }}>
        <option value="">Select rider</option>
        @foreach($riderOptions as $option)
        <option value="{{ $option->id }}"
            data-branch-id="{{ $option->branch_id ?? '' }}"
            {{ (int) $riderId === (int) $option->id ? 'selected' : '' }}>
            {{ trim(($option->rider_id ? $option->rider_id . ' - ' : '') . ($option->name ?? '')) }}
        </option>
        @endforeach
    </select>
</div>
@endif

<div class="mb-3" @if($lockVoucherType) style="display:none;" @endif>
    <label class="form-label">Voucher Type</label>
    <select id="voucherType" class="form-select form-select-sm select2" {{ ($editMode || $lockVoucherType) ? 'disabled' : '' }}>
        <option value="">Select</option>
        @foreach($voucherTypes as $code => $label)
        <option value="{{ $code }}" {{ (isset($voucher_type) && $voucher_type == $code) || $preselectedType === $code ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
@if($lockVoucherType)
<div class="mb-3">
    <label class="form-label">Voucher Type</label>
    <input type="text" class="form-control form-control-sm" value="{{ $voucherTypes[$preselectedType] ?? $preselectedType }}" readonly>
</div>
@endif

<input type="hidden" id="reload_page" value="1">
<input type="hidden" id="rider_id" value="{{ $rider?->id ?? '' }}">
<input type="hidden" id="rider_branch_id" value="{{ $rider?->branch_id ?? '' }}">
<input type="hidden" id="voucher_id" value="{{ $voucher_id ?? '' }}">
<input type="hidden" id="voucher_type" value="{{ $voucher_type ?? $preselectedType }}">
<input type="hidden" id="edit_mode" value="{{ $editMode ? '1' : '0' }}">
<input type="hidden" id="base_url" value="{{ url('/') }}">
<input type="hidden" id="preselected_type" value="{{ $preselectedType }}">
<input type="hidden" id="require_rider_select" value="{{ $requireRiderSelect ? '1' : '0' }}">

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
        let typeToUrl = urlsB64 ? JSON.parse(atob(urlsB64)) : {};

        const riderIdInput = document.getElementById('rider_id');
        const riderBranchInput = document.getElementById('rider_branch_id');
        const voucherId = document.getElementById('voucher_id').value;
        const voucherType = document.getElementById('voucher_type').value;
        const editMode = document.getElementById('edit_mode').value === '1';
        const preselectedType = document.getElementById('preselected_type').value || '';
        const requireRiderSelect = document.getElementById('require_rider_select').value === '1';
        const typeSelect = document.getElementById('voucherType');
        const $typeSelect = $(typeSelect);
        const riderSelect = document.getElementById('voucherRiderSelect');
        const $riderSelect = riderSelect ? $(riderSelect) : null;
        const modalParent = $('#modalTopbody').length ? $('#modalTopbody') : $(document.body);

        function currentRiderId() {
            return String(riderIdInput.value || '').trim();
        }

        function resolveUrl(template, riderId) {
            if (!template) return '';
            if (template.indexOf('__RID__') === -1) {
                return template;
            }
            return template.split('__RID__').join(String(riderId));
        }

        // Preserve original templates (may contain __RID__) for rider switches
        const urlTemplates = JSON.parse(JSON.stringify(typeToUrl));

        function urlsForRider(riderId) {
            const out = {};
            Object.keys(urlTemplates).forEach(function(code) {
                out[code] = resolveUrl(urlTemplates[code], riderId);
            });
            return out;
        }

        if ($.fn.select2 && !$typeSelect.hasClass('select2-hidden-accessible')) {
            $typeSelect.select2({
                width: '100%',
                allowClear: !preselectedType,
                placeholder: 'Select',
                dropdownParent: modalParent
            });
        }

        if ($riderSelect && $.fn.select2 && !$riderSelect.hasClass('select2-hidden-accessible')) {
            $riderSelect.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select rider',
                dropdownParent: modalParent
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
            const riderBranchId = riderBranchInput.value;
            if (riderBranchId) {
                branchInput.value = riderBranchId;
            } else if (!branchInput.value) {
                branchInput.value = '';
            }

            $(temp).find('script').each(function() {
                $.globalEval(this.text || this.textContent || this.innerHTML || '');
            });

            if (typeof window.initPaymentFieldsForm === 'function' && form.querySelector('[data-payment-fields-init]')) {
                window.initPaymentFieldsForm(form);
            }

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

                    $('#modalTopTitle').text('Record Rider Payment');
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

            const riderId = currentRiderId();
            if (requireRiderSelect && !riderId) {
                container.innerHTML = '<div class="alert alert-warning mb-0">Select a rider to continue.</div>';
                return;
            }

            typeToUrl = urlsForRider(riderId || '0');
            const url = typeToUrl[type] || '';
            if (!url) {
                container.innerHTML = '';
                return;
            }

            if (type === 'PAY' && !isEdit) {
                loadPaymentVoucherForm(url);
                return;
            }

            const fetchUrl = isEdit ? `${url}${url.indexOf('?') === -1 ? '?' : '&'}voucher_id=${voucherId}` : url;

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

        function activeType() {
            return preselectedType || typeSelect.value || '';
        }

        $typeSelect.on('change', function() {
            loadFormFor(this.value, false);
        });

        if ($riderSelect) {
            $riderSelect.on('change', function() {
                const option = this.options[this.selectedIndex];
                riderIdInput.value = this.value || '';
                riderBranchInput.value = option ? (option.getAttribute('data-branch-id') || '') : '';
                loadFormFor(activeType(), false);
            });
        }

        if (editMode && voucherType) {
            loadFormFor(voucherType, true);
        } else if (preselectedType && currentRiderId()) {
            loadFormFor(preselectedType, false);
        } else if (preselectedType && requireRiderSelect) {
            container.innerHTML = '<div class="alert alert-warning mb-0">Select a rider to open this voucher form.</div>';
        } else if (!requireRiderSelect && typeSelect.value) {
            loadFormFor(typeSelect.value, false);
        }
    })();
</script>
