window.initFixedAssetForm = function (options) {
    options = options || {};
    var categoryDefaultsUrl = options.categoryDefaultsUrl || '';
    var isEdit = !!options.isEdit;
    var lastMonthStart = options.lastMonthStart || '';

    function syncInServiceMinDate() {
        if ($('#acquisition_type').val() === 'opening_balance') {
            $('#in_service_date').removeAttr('min');
            return;
        }
        var acquisitionDate = $('#acquisition_date').val();
        if (acquisitionDate) {
            $('#in_service_date').attr('min', acquisitionDate);
            if ($('#in_service_date').val() && $('#in_service_date').val() < acquisitionDate) {
                $('#in_service_date').val(acquisitionDate);
            }
        }
    }

    function syncPastDepreciationSection() {
        var $section = $('#past-depreciation-section');
        if (!$section.length || !lastMonthStart) {
            return;
        }

        var acquisitionType = $('#acquisition_type').val();
        var inServiceDate = $('#in_service_date').val();
        var needsHandling = acquisitionType !== 'opening_balance'
            && inServiceDate
            && inServiceDate < lastMonthStart;

        if (needsHandling) {
            $section.show();
            $('.past-depreciation-option').prop('required', true);
        } else {
            $section.hide();
            $('.past-depreciation-option').prop('required', false);
            if (acquisitionType === 'opening_balance') {
                $('.past-depreciation-option').prop('checked', false);
            }
        }
    }

    function syncOpeningBalanceMode() {
        var isOpeningBalance = $('#acquisition_type').val() === 'opening_balance';
        var $statusSelectWrap = $('#asset-status-select-wrap');
        var $statusLockedWrap = $('#asset-status-locked-wrap');
        var $statusHelpWrap = $('#asset-status-help-wrap');
        var $openingHelpWrap = $('#asset-status-opening-help-wrap');
        var $acquisitionSection = $('#active-acquisition-section');

        if (isOpeningBalance) {
            $statusSelectWrap.hide();
            $statusLockedWrap.show();
            $statusHelpWrap.hide();
            $openingHelpWrap.show();
            if (!isEdit) {
                $('#asset_status_locked').val('active');
                $('#asset_status_display').val('Active');
            }
            $('#asset_status_locked').attr('name', 'status');
            $('#asset_status').prop('disabled', true).removeAttr('name');

            if ($acquisitionSection.length) {
                $acquisitionSection.hide();
            }
            setVoucherFieldsRequired(false);
        } else {
            $statusSelectWrap.show();
            $statusLockedWrap.hide();
            $statusHelpWrap.show();
            $openingHelpWrap.hide();
            $('#asset_status').prop('disabled', false).attr('name', 'status');
            $('#asset_status_locked').removeAttr('name');
            syncAcquisitionSections();
        }
    }

    function syncAcquisitionSections() {
        if ($('#acquisition_type').val() === 'opening_balance') {
            return;
        }

        var status = $('#asset_status').val();
        var posting = $('input[name="acquisition_posting"]:checked').val();
        var $activeSection = $('#active-acquisition-section');
        var $voucherSection = $('#acquisition-voucher-section');

        if (!$activeSection.length) {
            return;
        }

        if (status === 'active') {
            $activeSection.show();
            if (posting === 'post_now') {
                $voucherSection.show();
                setVoucherFieldsRequired(true);
            } else {
                $voucherSection.hide();
                setVoucherFieldsRequired(false);
            }
        } else {
            $activeSection.hide();
            $voucherSection.hide();
            setVoucherFieldsRequired(false);
        }
    }

    function setVoucherFieldsRequired(required) {
        $('#acquisition_credit_account_id, #voucher_trans_date, #voucher_billing_month, #voucher_reference_number')
            .prop('required', required);
    }

    function updateAssetAccountDisplay(accountId, accountName) {
        $('#asset_account_id').val(accountId || '');
        $('#asset_account_display').val(accountName || '');
    }

    function syncVoucherAmount() {
        var cost = parseFloat($('#acquisition_cost').val()) || 0;
        $('#acquisition_voucher_amount').val(cost > 0 ? cost.toFixed(2) : '');
    }

    function syncBikeHiddenName() {
        var $selectedBike = $('#bike_id option:selected');
        var label = $selectedBike.data('label') || $.trim($selectedBike.text());
        $('#asset_name_hidden').val(label || '');
    }

    function syncVehicleCategoryMode() {
        var $selected = $('#asset_category_id option:selected');
        var isVehicles = String($selected.attr('data-is-vehicles')) === '1';
        var $nameWrap = $('#asset-name-field-wrap');
        var $bikeWrap = $('#asset-bike-field-wrap');
        var $nameInput = $('#asset_name');
        var $hiddenName = $('#asset_name_hidden');

        if (!$nameWrap.length || !$bikeWrap.length) {
            return;
        }

        if (isVehicles) {
            $nameWrap.hide();
            $bikeWrap.show();
            $nameInput.prop('required', false).removeAttr('name');
            $('#bike_id').prop('required', true);
            $hiddenName.prop('disabled', false).attr('name', 'name');
            syncBikeHiddenName();
        } else {
            $bikeWrap.hide();
            $nameWrap.show();
            $('#bike_id').prop('required', false).val('');
            $hiddenName.prop('disabled', true).removeAttr('name');
            $nameInput.prop('required', true).attr('name', 'name');
        }
    }

    function applyCategoryDefaults() {
        var $selected = $('#asset_category_id option:selected');
        if (!$selected.val()) {
            return;
        }

        $('#depreciation_method').val($selected.data('method'));
        $('#depreciation_frequency').val($selected.data('frequency'));
        $('#useful_life_months').val($selected.data('life'));

        var cost = parseFloat($('#acquisition_cost').val()) || 0;
        var percent = parseFloat($selected.data('salvage-percent')) || 0;
        $('#salvage_value').val((cost * percent / 100).toFixed(2));

        var defaultsUrl = typeof options.getCategoryDefaultsUrl === 'function'
            ? options.getCategoryDefaultsUrl()
            : options.categoryDefaultsUrl;

        if (defaultsUrl) {
            $.get(defaultsUrl, { acquisition_cost: cost }, function (data) {
                updateAssetAccountDisplay(data.asset_account_id, data.asset_account_name);
            });
        } else {
            updateAssetAccountDisplay($selected.data('asset-account-id'), $selected.data('asset-account-name'));
        }

        syncVoucherAmount();
        syncVehicleCategoryMode();
    }

    if ($('#bike_id').length) {
        $('#bike_id').select2({
            dropdownParent: $('#modalTopbody'),
            allowClear: true,
            placeholder: 'Select bike'
        }).on('change', syncBikeHiddenName);
    }

    $('#branch_id').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select branch'
    });

    if ($('#acquisition_credit_account_id').length) {
        $('#acquisition_credit_account_id').select2({
            dropdownParent: $('#modalTopbody'),
            allowClear: true,
            placeholder: 'Select credit account'
        });
    }

    $('#asset_category_id').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select category'
    });

    $('#depreciation_method').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select depreciation method'
    });

    $('#acquisition_type').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select acquisition type'
    });

    $('#asset_status').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select status'
    });

    $('#depreciation_frequency').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: 'Select depreciation frequency'
    });

    $('#acquisition_type').on('change', function () {
        if ($(this).val() === 'opening_balance') {
            $('#acquisition_date_help').text('Date the asset is recorded in the system.');
            $('#depreciation_as_of_date').val($('#acquisition_date').val());
        } else {
            $('#acquisition_date_help').text('Date the asset was purchased.');
        }
        syncInServiceMinDate();
        syncOpeningBalanceMode();
        syncPastDepreciationSection();
    });

    $('#acquisition_date').on('change', function () {
        if ($('#acquisition_type').val() === 'opening_balance') {
            if (!$('#depreciation_as_of_date').data('userEdited')) {
                $('#depreciation_as_of_date').val($(this).val());
            }
        } else if (!$('#in_service_date').data('userEdited')) {
            $('#in_service_date').val($(this).val());
        }
        syncInServiceMinDate();
        syncPastDepreciationSection();
    });

    $('#depreciation_as_of_date').on('change input', function () {
        $(this).data('userEdited', true);
    });

    $('#in_service_date').on('change input', function () {
        $(this).data('userEdited', true);
        syncInServiceMinDate();
        syncPastDepreciationSection();
    });

    $('#asset_status').on('change', syncAcquisitionSections);
    $('input[name="acquisition_posting"]').on('change', syncAcquisitionSections);
    $('#asset_category_id, #acquisition_cost').on('change input', applyCategoryDefaults);
    $('#asset_category_id').on('change', syncVehicleCategoryMode);

    applyCategoryDefaults();
    syncInServiceMinDate();
    syncOpeningBalanceMode();
    syncPastDepreciationSection();
    syncVehicleCategoryMode();
    syncVoucherAmount();
};
