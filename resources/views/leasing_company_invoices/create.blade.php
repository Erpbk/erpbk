@extends('layouts.app')

@section('title', 'Create Leasing Company Invoice')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Create Leasing Company Invoice</h3>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <h4>Invoice Details</h4>
        </div>
        {!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'invoiceForm']) !!}
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Leasing Company <span class="text-danger">*</span></label>
                    <select name="leasing_company_id" id="leasing_company_id" class="form-control select2" required>
                        <option value="">Select Leasing Company</option>
                        @foreach($leasingCompanies as $company)
                        <option value="{{ $company->id }}"
                            {{ ($leasingCompany && $leasingCompany->id == $company->id) ? 'selected' : '' }}
                            data-rental-amount="{{ $company->rental_amount ?? 0 }}">
                            {{ $company->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="inv_date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label>Billing Month <span class="text-danger">*</span></label>
                    <input type="month" class="form-control" name="billing_month" value="{{ date('Y-m') }}" required>
                </div>
                <div class="col-md-6 form-group">
                    <label>Descriptions</label>
                    {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Invoice descriptions']) !!}
                </div>
                <div class="col-md-6 form-group">
                    <label>Notes</label>
                    {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Additional notes']) !!}
                </div>
            </div>

            <hr>
            <h5>Bikes & Rental Amounts</h5>
            <div id="bikes-container">
                <div class="row bike-row" data-index="0">
                    <div class="col-md-4 form-group">
                        <label>Bike <span class="text-danger">*</span></label>
                        <select name="bike_id[]" class="form-control bike-select" required>
                            <option value="">Select Bike</option>
                            @foreach($bikes as $bike)
                            <option value="{{ $bike->id }}">{{ $bike->plate }} - {{ $bike->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Tax Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="tax_rate[]" class="form-control tax-rate"
                            value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}" placeholder="5.00" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Rental Amount (AED) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="rental_amount[]" class="form-control rental-amount"
                            value="{{ ($leasingCompany && $leasingCompany->rental_amount) ? $leasingCompany->rental_amount : 0 }}"
                            placeholder="0.00" required>
                    </div>
                    <div class="col-md-1 form-group d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-remove-row" style="display:none;"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <button type="button" class="btn btn-success" id="add-bike-row"><i class="fa fa-plus"></i> Add Bike</button>
                </div>
            </div>
        </div>
        <div class="card-footer">
            {!! Form::submit('Create Invoice', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('leasingCompanyInvoices.index') }}" class="btn btn-default">Cancel</a>
        </div>
        {!! Form::close() !!}
    </div>
</div>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        var allBikes = @json($bikes->map(function($bike) {
            return ['id' => $bike->id, 'plate' => $bike->plate, 'model' => $bike->model];
        }));
        var bikesByCompany = {};

        // Load bikes when leasing company changes
        $('#leasing_company_id').on('change', function() {
            var companyId = $(this).val();
            var rentalAmount = $(this).find('option:selected').data('rental-amount') || 0;

            if (companyId) {
                // Fetch bikes for this company via AJAX
                $.ajax({
                    url: '{{ route("leasingCompanies.getBikes", ":id") }}'.replace(':id', companyId),
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        // Update bike dropdowns
                        var bikeOptions = '<option value="">Select Bike</option>';
                        if (response.bikes && response.bikes.length > 0) {
                            response.bikes.forEach(function(bike) {
                                bikeOptions += '<option value="' + bike.id + '">' + bike.plate + ' - ' + bike.model + '</option>';
                            });
                        }
                        $('.bike-select').html(bikeOptions);

                        // Update rental amounts
                        var newRentalAmount = response.rental_amount || rentalAmount;
                        $('.rental-amount').val(newRentalAmount);
                    },
                    error: function() {
                        alert('Error loading bikes for this leasing company.');
                    }
                });
            } else {
                $('.bike-select').html('<option value="">Select Bike</option>');
            }
        });

        // Auto-fetch rental amount when bike is selected
        $(document).on('change', '.bike-select', function() {
            var companyId = $('#leasing_company_id').val();
            if (companyId) {
                var rentalAmount = $('#leasing_company_id option:selected').data('rental-amount') || 0;
                $(this).closest('.bike-row').find('.rental-amount').val(rentalAmount);
            }
        });

        // Add new bike row
        $('#add-bike-row').on('click', function() {
            var index = $('.bike-row').length;
            var companyId = $('#leasing_company_id').val();
            var rentalAmount = $('#leasing_company_id option:selected').data('rental-amount') || 0;

            var bikeOptions = '<option value="">Select Bike</option>';
            if (allBikes && allBikes.length > 0) {
                allBikes.forEach(function(bike) {
                    bikeOptions += '<option value="' + bike.id + '">' + bike.plate + ' - ' + bike.model + '</option>';
                });
            }

            var defaultTaxRate = {
                {
                    \
                    App\ Helpers\ Common::getSetting('vat_percentage') ?? 5
                }
            };

            var html = `
            <div class="row bike-row" data-index="${index}">
                <div class="col-md-4 form-group">
                    <label>Bike <span class="text-danger">*</span></label>
                    <select name="bike_id[]" class="form-control bike-select" required>
                        ${bikeOptions}
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Tax Rate (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="tax_rate[]" class="form-control tax-rate" 
                        value="${defaultTaxRate}" placeholder="5.00" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Rental Amount (AED) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="rental_amount[]" class="form-control rental-amount" 
                        value="${rentalAmount}" placeholder="0.00" required>
                </div>
                <div class="col-md-1 form-group d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        `;
            $('#bikes-container').append(html);
            updateRemoveButtons();
        });

        // Remove bike row
        $(document).on('click', '.btn-remove-row', function() {
            $(this).closest('.bike-row').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            $('.bike-row').each(function(index) {
                if (index === 0) {
                    $(this).find('.btn-remove-row').hide();
                } else {
                    $(this).find('.btn-remove-row').show();
                }
            });
        }

        // Form submission
        $('#invoiceForm').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = "{{ route('leasingCompanyInvoices.index') }}";
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMsg = 'Please fix the following errors:\n';
                    for (var key in errors) {
                        if (Array.isArray(errors[key])) {
                            errorMsg += '- ' + errors[key][0] + '\n';
                        } else {
                            errorMsg += '- ' + errors[key] + '\n';
                        }
                    }
                    alert(errorMsg);
                }
            });
        });
    });
</script>
@endsection