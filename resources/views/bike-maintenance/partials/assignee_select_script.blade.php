{{-- Shared bike-assignee loader for maintenance forms --}}
<script>
(function () {
    if (window.BikeMaintenanceAssignees) {
        return;
    }

    window.BikeMaintenanceAssignees = {
        url: @json(route('bikeMaintenance.getAssignees')),
        token: @json(csrf_token()),

        destroySelect2: function ($select) {
            if (!$select.length || typeof $select.select2 !== 'function') {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            // Remove orphaned Select2 containers left beside the native select
            $select.next('.select2-container').remove();
            $select.removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            $select.find('option').removeAttr('data-select2-id');
        },

        initSelect2: function ($select) {
            if (!$select.length || typeof $select.select2 !== 'function') {
                return;
            }
            this.destroySelect2($select);
            $select.select2({
                allowClear: true,
                placeholder: 'Select assigned user',
                dropdownParent: $('#formajax').length ? $('#formajax') : ($('#modalTopbody').length ? $('#modalTopbody') : $(document.body)),
                width: '100%'
            });
        },

        applyAssigneeSelection: function ($assignee, riderId, rentalId, customerType, options) {
            options = options || {};
            var $riderHidden = $('#rider_id_hidden');
            var $rentalHidden = $('#rental_id_hidden');

            $riderHidden.val(riderId || '');
            $rentalHidden.val(rentalId || '');

            var noAssignee = !riderId && !rentalId;
            var isGarageCustomer = customerType === 'garage';

            if (typeof window.toggleRiderChargeOption === 'function') {
                window.toggleRiderChargeOption(isGarageCustomer, noAssignee, !!options.preserveChargeTo);
            }
        },

        populate: function (payload, preferredKey) {
            var $assignee = $('#assignee_select');
            if (!$assignee.length) {
                return;
            }

            var selected = preferredKey || payload.selected || '';
            var html = '<option value="">No User Assigned</option>';

            (payload.assignees || []).forEach(function (row) {
                html += '<option value="' + row.key + '"'
                    + ' data-type="' + row.type + '"'
                    + ' data-id="' + row.id + '"'
                    + ' data-customer-type="' + (row.customer_type || '') + '"'
                    + (selected && selected === row.key ? ' selected' : '')
                    + '>' + $('<div>').text(row.label).html() + '</option>';
            });

            this.destroySelect2($assignee);
            $assignee.html(html);

            if (selected && $assignee.find('option[value="' + selected + '"]').length) {
                $assignee.val(selected);
            } else {
                $assignee.val('');
            }

            this.initSelect2($assignee);

            if (payload.previous_km !== undefined && $('#previous_km').length) {
                $('#previous_km').val(payload.previous_km);
            }
            if (payload.maintenance_km !== undefined && $('#maintenance_km').length) {
                $('#maintenance_km').val(payload.maintenance_km || '');
            }

            // Apply hidden ids / charge rules without relying on a second change event
            var $opt = $assignee.find('option:selected');
            var type = $opt.data('type');
            var id = $opt.data('id');
            var customerType = $opt.data('customer-type') || '';
            var preserve = !!this._preserveChargeToOnNextPopulate;
            this._preserveChargeToOnNextPopulate = false;

            if (type === 'rider' && id) {
                this.applyAssigneeSelection($assignee, id, null, null, { preserveChargeTo: preserve });
            } else if (type === 'company' && id) {
                this.applyAssigneeSelection($assignee, null, id, customerType, { preserveChargeTo: preserve });
            } else {
                this.applyAssigneeSelection($assignee, null, null, null, { preserveChargeTo: preserve });
            }
        },

        clear: function () {
            var $assignee = $('#assignee_select');
            if ($assignee.length) {
                this.destroySelect2($assignee);
                $assignee.html('<option value="">No User Assigned</option>').val('');
                this.initSelect2($assignee);
            }
            this.applyAssigneeSelection($assignee, null, null, null);
            if ($('#previous_km').length) {
                $('#previous_km').val('');
            }
            if ($('#maintenance_km').length) {
                $('#maintenance_km').val('');
            }
        },

        loadForBike: function (bikeId, options) {
            options = options || {};
            var self = this;

            if (!bikeId) {
                self.clear();
                if (typeof options.onLoaded === 'function') {
                    options.onLoaded(null);
                }
                return;
            }

            self._preserveChargeToOnNextPopulate = !!options.preserveChargeTo;

            $.ajax({
                type: 'POST',
                url: self.url,
                dataType: 'json',
                data: {
                    _token: self.token,
                    bike_id: bikeId,
                    maintenance_date: options.maintenanceDate || $('#maintenance_date').val() || '',
                    selected_rider_id: options.selectedRiderId || '',
                    selected_rental_company_id: options.selectedRentalCompanyId || ''
                },
                success: function (res) {
                    if (!res || !res.success) {
                        self.clear();
                        return;
                    }
                    self.populate(res, options.preferredKey || null);
                    if (typeof options.onLoaded === 'function') {
                        options.onLoaded(res);
                    }
                },
                error: function () {
                    self.clear();
                }
            });
        },

        bind: function (config) {
            config = config || {};
            var self = this;
            var $bikeSelect = $(config.bikeSelect || '#bike_select');
            var fixedBikeId = config.fixedBikeId || null;
            var selectedRiderId = config.selectedRiderId || '';
            var selectedRentalCompanyId = config.selectedRentalCompanyId || '';
            var stickySelection = !!(selectedRiderId || selectedRentalCompanyId);

            $(document)
                .off('change.bmAssignee', '#assignee_select')
                .on('change.bmAssignee', '#assignee_select', function () {
                    var $opt = $(this).find('option:selected');
                    var type = $opt.data('type');
                    var id = $opt.data('id');
                    var customerType = $opt.data('customer-type') || '';

                    if (type === 'rider' && id) {
                        self.applyAssigneeSelection($(this), id, null, null);
                    } else if (type === 'company' && id) {
                        self.applyAssigneeSelection($(this), null, id, customerType);
                    } else {
                        self.applyAssigneeSelection($(this), null, null, null);
                    }

                    if (typeof window.calculateOverdue === 'function') {
                        window.calculateOverdue();
                    } else if (typeof config.onAssigneeChange === 'function') {
                        config.onAssigneeChange();
                    }
                });

            if ($bikeSelect.length) {
                $(document)
                    .off('change.bmBike', config.bikeSelect || '#bike_select')
                    .on('change.bmBike', config.bikeSelect || '#bike_select', function () {
                        var bikeId = $(this).val();
                        var opts = {
                            maintenanceDate: $('#maintenance_date').val() || '',
                            onLoaded: function () {
                                if (typeof config.onBikeLoaded === 'function') {
                                    config.onBikeLoaded();
                                }
                            }
                        };

                        if (stickySelection) {
                            opts.selectedRiderId = selectedRiderId;
                            opts.selectedRentalCompanyId = selectedRentalCompanyId;
                            opts.preserveChargeTo = true;
                            stickySelection = false;
                        }

                        self.loadForBike(bikeId, opts);
                        if (typeof $(this).select2 === 'function') {
                            try { $(this).select2('close'); } catch (e) {}
                        }
                    });
            }

            $(document)
                .off('change.bmMaintDate', '#maintenance_date')
                .on('change.bmMaintDate', '#maintenance_date', function () {
                    var bikeId = fixedBikeId || $bikeSelect.val();
                    if (!bikeId) {
                        return;
                    }
                    // Re-resolve default for the date, ignoring sticky edit selection
                    self.loadForBike(bikeId, {
                        maintenanceDate: $(this).val() || ''
                    });
                });

            var initialBikeId = fixedBikeId || $bikeSelect.val();
            if (initialBikeId) {
                self.loadForBike(initialBikeId, {
                    maintenanceDate: $('#maintenance_date').val() || '',
                    selectedRiderId: selectedRiderId,
                    selectedRentalCompanyId: selectedRentalCompanyId,
                    preserveChargeTo: !!config.preserveChargeTo || !!(selectedRiderId || selectedRentalCompanyId),
                    onLoaded: function () {
                        if (typeof config.onBikeLoaded === 'function') {
                            config.onBikeLoaded();
                        }
                    }
                });
                stickySelection = false;
            } else {
                self.clear();
            }
        }
    };
})();
</script>
