@extends('layouts.app')

@section('title', 'Create Invoice - ' . $leasingCompany->name)
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Create Invoice - {{ $leasingCompany->name }}</h3>
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
        {!! Form::open(['route' => ['leasingCompanies.storeInvoice', $leasingCompany->id], 'id' => 'invoiceForm']) !!}
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="inv_date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label>Billing Month <span class="text-danger">*</span></label>
                    <input type="month" class="form-control" name="billing_month" value="{{ date('Y-m') }}" required>
                </div>
                <div class="col-md-6 form-group">
                    <label>Leasing Company</label>
                    <input type="text" class="form-control" value="{{ $leasingCompany->name }}" readonly>
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
                        <label>Rental Amount (AED) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="rental_amount[]" class="form-control rental-amount"
                            value="0" placeholder="0.00" required>
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
            <a href="{{ route('leasingCompanies.index') }}" class="btn btn-default">Cancel</a>
        </div>
        {!! Form::close() !!}
    </div>
</div>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        // Get all bikes for dropdown
        var allBikes = @json($bikes-> map(function($bike) {
            return ['id' => $bike-> id, 'plate' => $bike-> plate, 'model' => $bike-> model];
        }));

        // Add new bike row
        $('#add-bike-row').on('click', function() {
            var index = $('.bike-row').length;
            var html = `
            <div class="row bike-row" data-index="${index}">
                <div class="col-md-4 form-group">
                    <label>Bike <span class="text-danger">*</span></label>
                    <select name="bike_id[]" class="form-control bike-select" required>
                        <option value="">Select Bike</option>
                        ${allBikes.map(bike => `<option value="${bike.id}">${bike.plate} - ${bike.model}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Rental Amount (AED) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="rental_amount[]" class="form-control rental-amount" 
                        value="0" placeholder="0.00" required>
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
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = "{{ route('leasingCompanies.index') }}";
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMsg = 'Please fix the following errors:\n';
                    for (var key in errors) {
                        errorMsg += '- ' + errors[key][0] + '\n';
                    }
                    alert(errorMsg);
                }
            });
        });
    });
</script>
@endsection