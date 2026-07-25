<div class="row">
    <div class="col-md-9">
        <div class="row">
            <div class="form-group col-sm-4">
                <label class="">Bike:</label>
                <select class="form-select select2" required id="bike_id" name="bike_id">
                    <option value=""></option>
                    @foreach($bikes as $b)
                    <option value="{{ $b->id }}" {{ (isset($salik) && $salik->bike_id == $b->id) ? 'selected' : '' }}>
                        {{ $b->plate }} - {{ $b->leasingCompany ? $b->leasingCompany->name : 'Own Bike' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-sm-4">
                <label class="">Rider:</label>
                <select class="form-select select2" id="rider_account" name="rider_id"
                    data-selected="{{ isset($salik) ? ($salik->rider_id ?? '') : '' }}"></select>
            </div>
            <div class="form-group col-sm-4">
                <label class="">Rental Company:</label>
                <select class="form-select select2" id="company_account" name="rental_company_id"
                    data-selected="{{ isset($salik) ? ($salik->rental_company_id ?? '') : '' }}"></select>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4">
                {!! Form::label('transaction_id', 'Transaction ID:', ['class' => 'required']) !!}
                {!! Form::text('transaction_id', $salik->transaction_id ?? '', ['class' => 'form-control', 'maxlength' => 255, 'required']) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('trip_date', 'Trip Date:', ['class' => 'required']) !!}
                {!! Form::date('trip_date', isset($salik->trip_date) ? \Carbon\Carbon::parse($salik->trip_date)->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'trip_date', 'required']) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('trip_time', 'Trip Time:', ['class' => 'required']) !!}
                {!! Form::time('trip_time', isset($salik->trip_time) ? \Carbon\Carbon::parse($salik->trip_time)->format('H:i') : null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
                {!! Form::month('billing_month', isset($salik) && $salik->billing_month ? \Carbon\Carbon::parse($salik->billing_month)->format('Y-m') : null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('transaction_post_date', 'Transaction Post Date:') !!}
                {!! Form::date('transaction_post_date', isset($salik->transaction_post_date) ? \Carbon\Carbon::parse($salik->transaction_post_date)->format('Y-m-d') : null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('salik_payable_account', 'Credit Account:', ['class' => 'required']) !!}
                <input type="hidden" name="salik_payable_account_id" value="{{ $salikPayableAccount->id }}">
                <input type="text" value="{{ $salikPayableAccount->name ?? 'Salik Payable' }}" readonly class="form-control">
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('toll_gate', 'Toll Gate:') !!}
                {!! Form::text('toll_gate', $salik->toll_gate ?? '', ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('direction', 'Direction:') !!}
                {!! Form::text('direction', $salik->direction ?? '', ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('tag_number', 'Tag Number:') !!}
                {!! Form::text('tag_number', $salik->tag_number ?? '', ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="form-group col-sm-12">
        {!! Form::label('details', 'Detail:', ['class' => 'required']) !!}
        {!! Form::textarea('details', $salik->details ?? '', ['class' => 'form-control', 'maxlength' => 5000, 'rows' => 3, 'required']) !!}
    </div>
</div>

<div class="row mt-4 mb-4">
    <div class="col-sm-12">
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (AED)</th>
                    <th>VAT (%)</th>
                    <th>VAT (AED)</th>
                    <th>Total (AED)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $salikVatAmt = isset($salik) ? (float)($salik->salik_vat_amount ?? 0) : 0;
                    $adminVatAmt = isset($salik) ? (float)($salik->admin_vat_amount ?? 0) : 0;
                @endphp
                <tr>
                    <td><strong>Salik Amount</strong></td>
                    <td>{!! Form::number('amount', $salik->amount ?? null, ['class' => 'form-control amount-input', 'step' => 'any', 'id' => 'salik_amount', 'placeholder' => '0.00', 'required', 'min' => '0.01']) !!}</td>
                    <td>{!! Form::number('salik_vat', $salik->salik_vat ?? 0, ['class' => 'form-control vat-percent', 'step' => 'any', 'id' => 'salik_vat_percent', 'placeholder' => '0']) !!}</td>
                    <td>{!! Form::number('salik_vat_amount', $salikVatAmt, ['class' => 'form-control vat-amount', 'step' => 'any', 'id' => 'salik_vat_amount', 'readonly']) !!}</td>
                    <td>{!! Form::number('salik_total', isset($salik) ? (float)$salik->amount + $salikVatAmt : 0, ['class' => 'form-control total-amount', 'step' => 'any', 'id' => 'salik_total', 'readonly']) !!}</td>
                </tr>
                <tr>
                    <td><strong>Admin Charges</strong></td>
                    <td>{!! Form::number('admin_fee', $salik->admin_charges ?? 0, ['class' => 'form-control amount-input', 'step' => 'any', 'id' => 'admin_amount', 'placeholder' => '0.00', 'min' => '0']) !!}</td>
                    <td>{!! Form::number('admin_vat', $salik->admin_vat ?? 0, ['class' => 'form-control vat-percent', 'step' => 'any', 'id' => 'admin_vat_percent', 'placeholder' => '0']) !!}</td>
                    <td>{!! Form::number('admin_vat_amount', $adminVatAmt, ['class' => 'form-control vat-amount', 'step' => 'any', 'id' => 'admin_vat_amount', 'readonly']) !!}</td>
                    <td>{!! Form::number('admin_total', isset($salik) ? (float)($salik->admin_charges ?? 0) + $adminVatAmt : 0, ['class' => 'form-control total-amount', 'step' => 'any', 'id' => 'admin_total', 'readonly']) !!}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td>
                    <td>{!! Form::number('vat', $salik->vat ?? 0, ['class' => 'form-control', 'step' => 'any', 'id' => 'vat_total', 'readonly', 'style' => 'font-weight: bold; background: #f0f0f0;']) !!}</td>
                    <td>{!! Form::number('total_amount_display', $salik->total_amount ?? 0, ['class' => 'form-control', 'step' => 'any', 'id' => 'grand_total', 'readonly', 'style' => 'font-weight: bold; background: #f0f0f0;']) !!}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script type="text/javascript">
    var syncingPartySelects = false;

    function salikFormRoot() {
        return $('#formajax').length ? $('#formajax') : $('#modalTopbody');
    }

    function salikEl(selector) {
        return salikFormRoot().find(selector);
    }

    function calculateRow(amountId, vatPercentId, vatAmountId, totalId) {
        let amount = parseFloat(salikEl('#' + amountId).val()) || 0;
        let vatPercent = parseFloat(salikEl('#' + vatPercentId).val()) || 0;
        let vatAmount = (amount * vatPercent) / 100;
        let total = amount + vatAmount;
        salikEl('#' + vatAmountId).val(vatAmount.toFixed(2));
        salikEl('#' + totalId).val(total.toFixed(2));
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let salikTotal = parseFloat(salikEl('#salik_total').val()) || 0;
        let adminTotal = parseFloat(salikEl('#admin_total').val()) || 0;
        let salikVat = parseFloat(salikEl('#salik_vat_amount').val()) || 0;
        let adminVat = parseFloat(salikEl('#admin_vat_amount').val()) || 0;
        salikEl('#grand_total').val((salikTotal + adminTotal).toFixed(2));
        salikEl('#vat_total').val((salikVat + adminVat).toFixed(2));
    }

    function destroyPartySelect2() {
        ['#rider_account', '#company_account'].forEach(function(sel) {
            var $el = salikEl(sel);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
        });
    }

    function initPartySelect2() {
        destroyPartySelect2();
        salikEl('#rider_account, #company_account').filter(':visible').each(function() {
            $(this).select2({
                allowClear: true,
                placeholder: 'Select',
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
            });
        });
    }

    function applyPartyMutualExclusion() {
        if (syncingPartySelects) {
            return;
        }
        syncingPartySelects = true;

        var $rider = salikEl('#rider_account');
        var $company = salikEl('#company_account');
        var riderVal = $rider.val();
        var companyVal = $company.val();

        if (riderVal) {
            $company.val(null).prop('disabled', true);
            $rider.prop('disabled', false);
        } else if (companyVal) {
            $rider.val(null).prop('disabled', true);
            $company.prop('disabled', false);
        } else {
            $rider.prop('disabled', false);
            $company.prop('disabled', false);
        }

        $rider.add($company).trigger('change.select2');
        syncingPartySelects = false;
    }

    function applyTripDatePartySelection(selectedRiderId, selectedCompanyId) {
        syncingPartySelects = true;
        var $rider = salikEl('#rider_account');
        var $company = salikEl('#company_account');

        initPartySelect2();

        if (selectedRiderId) {
            $rider.prop('disabled', false).val(String(selectedRiderId)).trigger('change.select2');
            $company.val(null).prop('disabled', true).trigger('change.select2');
        } else if (selectedCompanyId) {
            $company.prop('disabled', false).val(String(selectedCompanyId)).trigger('change.select2');
            $rider.val(null).prop('disabled', true).trigger('change.select2');
        } else {
            $rider.prop('disabled', false);
            $company.prop('disabled', false);
        }

        syncingPartySelects = false;
    }

    function selectbike(id, useTripDateParty) {
        var $rider = salikEl('#rider_account');
        var $company = salikEl('#company_account');

        if (!id) {
            destroyPartySelect2();
            $rider.html('').prop('disabled', false).closest('.form-group').hide();
            $company.html('').prop('disabled', false).closest('.form-group').hide();
            return;
        }

        var tripDate = salikEl('#trip_date').val();
        var selectedRiderId = '';
        var selectedCompanyId = '';

        if (!useTripDateParty) {
            selectedRiderId = $rider.attr('data-selected') || '';
            selectedCompanyId = $company.attr('data-selected') || '';
        }

        $.ajax({
            type: 'post',
            url: '{{ route("salik.getriderbybikedate") }}',
            data: {
                bike_id: id,
                trip_date: tripDate,
                selected_rider_id: selectedRiderId,
                selected_rental_company_id: selectedCompanyId,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(res) {
                destroyPartySelect2();

                if (res.riders) {
                    $rider.html(res.riders).prop('disabled', false).closest('.form-group').show();
                } else {
                    $rider.html('').val(null).prop('disabled', false).closest('.form-group').hide();
                }
                if (res.companies) {
                    $company.html(res.companies).prop('disabled', false).closest('.form-group').show();
                } else {
                    $company.html('').val(null).prop('disabled', false).closest('.form-group').hide();
                }

                $rider.removeAttr('data-selected');
                $company.removeAttr('data-selected');

                if (res.riders || res.companies) {
                    applyTripDatePartySelection(res.selected_rider_id, res.selected_rental_company_id);
                }
            }
        });
    }

    (function initSalikFields() {
        var $root = salikFormRoot();
        if (!$root.length) {
            return;
        }

        $root.find('.select2').not('#rider_account, #company_account').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
        });
        $root.find('#rider_account').closest('.form-group').hide();
        $root.find('#company_account').closest('.form-group').hide();

        var selectedBikeId = @if(isset($salik) && $salik->bike_id) '{{ $salik->bike_id }}' @else false @endif;
        if (selectedBikeId) {
            selectbike(selectedBikeId, false);
        }

        $root.off('change.salikBike', '#bike_id').on('change.salikBike', '#bike_id', function() {
            var sticky = salikEl('#rider_account').attr('data-selected') || salikEl('#company_account').attr('data-selected');
            selectbike($(this).val(), !sticky);
        });

        $root.off('change.salikTrip input.salikTrip', '#trip_date')
            .on('change.salikTrip input.salikTrip', '#trip_date', function() {
                var bikeId = salikEl('#bike_id').val();
                if (!bikeId) {
                    return;
                }
                salikEl('#rider_account').removeAttr('data-selected');
                salikEl('#company_account').removeAttr('data-selected');
                selectbike(bikeId, true);
            });

        $root.off('change.salikParty', '#rider_account, #company_account')
            .on('change.salikParty', '#rider_account, #company_account', function() {
                applyPartyMutualExclusion();
            });

        $root.off('input.salikVat', '#salik_amount, #salik_vat_percent, #admin_amount, #admin_vat_percent')
            .on('input.salikVat', '#salik_amount, #salik_vat_percent', function() {
                calculateRow('salik_amount', 'salik_vat_percent', 'salik_vat_amount', 'salik_total');
            })
            .on('input.salikVat', '#admin_amount, #admin_vat_percent', function() {
                calculateRow('admin_amount', 'admin_vat_percent', 'admin_vat_amount', 'admin_total');
            });

        calculateRow('salik_amount', 'salik_vat_percent', 'salik_vat_amount', 'salik_total');
        calculateRow('admin_amount', 'admin_vat_percent', 'admin_vat_amount', 'admin_total');
    })();
</script>
