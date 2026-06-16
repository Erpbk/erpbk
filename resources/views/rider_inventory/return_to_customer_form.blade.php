@extends('layouts.app')

@section('title', 'Return Inventory to Customer')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Return Inventory to Customer</h3>
                <small class="text-muted">Mark returned rider inventory as handed back to the customer.</small>
            </div>
            <a href="{{ route('RiderInventory.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> Back
            </a>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    <form method="POST" action="{{ route('RiderInventory.returnToCustomerStore') }}" id="returnToCustomerForm">
        @csrf
        <div class="card mb-3">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="customer_id" class="form-label required">Customer</label>
                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                        <option value="">Select customer...</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}{{ $customer->company_name ? ' — ' . $customer->company_name : '' }}{{ (int) $customer->status !== 1 ? ' (Inactive)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="return_to_customer_date" class="form-label required">Return to Customer Date</label>
                    <input type="date" name="return_to_customer_date" id="return_to_customer_date" class="form-control"
                        value="{{ old('return_to_customer_date', date('Y-m-d')) }}" required>
                    @error('return_to_customer_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-primary w-100" id="loadAssignmentsBtn">
                        <i class="ti ti-search"></i> Load Returned Items
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Returned Items Available for Customer</h5>
                <span class="badge bg-secondary" id="assignmentCountBadge">0 items</span>
            </div>
            <div class="card-body table-responsive" id="returnToCustomerTableWrapper">
                <p class="text-muted mb-0">Select a customer and click <strong>Load Returned Items</strong> to see eligible inventory.</p>
            </div>
            <div class="card-footer text-end d-none" id="returnToCustomerFooter">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-truck-return"></i> Mark Selected as Returned to Customer
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('page-scripts')
<script>
$(function () {
    $('#customer_id').select2({
        allowClear: true,
        width: '100%',
        placeholder: 'Select customer...'
    });

    function loadAssignments() {
        const customerId = $('#customer_id').val();
        if (!customerId) {
            alert('Please select a customer first.');
            return;
        }

        $('#returnToCustomerTableWrapper').html('<p class="text-muted mb-0">Loading...</p>');
        $('#returnToCustomerFooter').addClass('d-none');

        $.get('{{ route('RiderInventory.returnToCustomerAssignments') }}', { customer_id: customerId })
            .done(function (response) {
                $('#returnToCustomerTableWrapper').html(response.tableHtml);
                $('#assignmentCountBadge').text(response.count + ' item(s)');
                if (response.count > 0) {
                    $('#returnToCustomerFooter').removeClass('d-none');
                }
            })
            .fail(function () {
                $('#returnToCustomerTableWrapper').html('<p class="text-danger mb-0">Failed to load assignments.</p>');
            });
    }

    $('#loadAssignmentsBtn').on('click', loadAssignments);

    @if(old('customer_id'))
    loadAssignments();
    @endif

    $('#returnToCustomerForm').on('submit', function (e) {
        const checked = $('input[name="assignment_ids[]"]:checked').length;
        if (!checked) {
            e.preventDefault();
            alert('Please select at least one item to return to customer.');
        }
    });

    $(document).on('change', '#selectAllReturnToCustomer', function () {
        $('input[name="assignment_ids[]"]').prop('checked', $(this).is(':checked'));
    });
});
</script>
@endpush
