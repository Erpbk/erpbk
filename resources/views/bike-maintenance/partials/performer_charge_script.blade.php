<script>
(function ($) {
    function isInternalPerformer() {
        var $opt = $('#bm_performer_select option:selected');
        return $opt.length && String($opt.data('garage-type') || '') === 'internal';
    }

    function applyCompanyChargeRules($row) {
        if (!$row || !$row.length) {
            return;
        }
        var $charge = $row.find('select[name="charge_to[]"]');
        var $item = $row.find('select.item, select[name="item_id[]"]').first();
        var $rate = $row.find('.rate');
        var $vat = $row.find('.vat');
        var chargeTo = $charge.val();
        var internalCompany = isInternalPerformer() && chargeTo === 'Company';

        if (internalCompany) {
            var cost = parseFloat($item.find('option:selected').data('cost'));
            if (isNaN(cost)) {
                cost = 0;
            }
            $rate.val(cost.toFixed(2)).prop('readonly', true);
            $vat.val('0.00').prop('readonly', true);
            if (typeof setItemTotal === 'function') {
                setItemTotal($row);
            }
            if (typeof setTotal === 'function') {
                setTotal();
            }
        } else {
            $rate.prop('readonly', false);
            $vat.prop('readonly', false);
        }
    }

    function refreshAllCompanyChargeRows() {
        $('#rows-container .row').each(function () {
            applyCompanyChargeRules($(this));
        });
    }

    window.BikeMaintenancePerformerCharge = {
        applyRow: applyCompanyChargeRules,
        refreshAll: refreshAllCompanyChargeRows,
        isInternal: isInternalPerformer
    };

    $(document).on('change', '#bm_performer_select', function () {
        refreshAllCompanyChargeRows();
    });

    $(document).on('change', 'select[name="charge_to[]"]', function () {
        applyCompanyChargeRules($(this).closest('.row'));
    });

    $(document).on('change', '#rows-container select.item, #rows-container select[name="item_id[]"]', function () {
        applyCompanyChargeRules($(this).closest('.row'));
    });

    $(document).ready(function () {
        refreshAllCompanyChargeRows();
    });
})(jQuery);
</script>
