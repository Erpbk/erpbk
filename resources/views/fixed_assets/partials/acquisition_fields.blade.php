<div class="form-group col-sm-6">
    {!! Form::label('acquisition_type', 'Acquisition Type:') !!}
    {!! Form::select('acquisition_type', \App\Models\FixedAsset::acquisitionTypes(), $acquisitionTypeValue ?? 'new_purchase', ['class' => 'form-control', 'id' => 'acquisition_type', 'required' => true]) !!}
</div>

<div id="opening-balance-fields" class="row" style="display: none;">
    <div class="form-group col-sm-6">
        {!! Form::label('opening_accumulated_depreciation', 'Opening Accumulated Depreciation:') !!}
        {!! Form::number('opening_accumulated_depreciation', $openingAccumulatedValue ?? 0, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'id' => 'opening_accumulated_depreciation']) !!}
        <small class="text-muted">Depreciation already taken before this asset was added to the system.</small>
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('depreciation_as_of_date', 'Depreciation As Of Date:') !!}
        {!! Form::date('depreciation_as_of_date', $depreciationAsOfDateValue ?? date('Y-m-d'), ['class' => 'form-control', 'id' => 'depreciation_as_of_date']) !!}
        <small class="text-muted">Date the opening accumulated depreciation balance applies to.</small>
    </div>
    <div class="col-sm-12">
        <div class="alert alert-info py-2 mb-0">
            <small>For opening balance assets, enter the original <strong>in-service date</strong> when usage started. Acquisition is posted automatically (asset debit, accumulated depreciation credit, net balance to Opening Balance Equity).</small>
        </div>
    </div>
</div>

<div id="donation-fields" class="row" style="display: none;">
    <div class="col-sm-12">
        <div class="alert alert-info py-2 mb-0">
            <small>Donated assets are recorded at fair value. Depreciation starts from the in-service date with no opening accumulated depreciation.</small>
        </div>
    </div>
</div>

<script>
$(function () {
    function toggleAcquisitionTypeFields() {
        var type = $('#acquisition_type').val();
        var $openingBalance = $('#opening-balance-fields');
        var $donation = $('#donation-fields');

        $openingBalance.hide();
        $donation.hide();
        $('#opening_accumulated_depreciation').prop('required', false);
        $('#depreciation_as_of_date').prop('required', false);

        if (type === 'opening_balance') {
            $openingBalance.show();
            $('#opening_accumulated_depreciation').prop('required', true);
            $('#depreciation_as_of_date').prop('required', true);
        } else if (type === 'donation') {
            $donation.show();
            $('#opening_accumulated_depreciation').val(0);
        } else {
            $('#opening_accumulated_depreciation').val(0);
        }
    }

    $('#acquisition_type').on('change', toggleAcquisitionTypeFields);
    toggleAcquisitionTypeFields();
});
</script>
