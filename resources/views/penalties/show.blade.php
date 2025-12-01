@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Penalty Details</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default float-right" href="{{ route('penalties.index') }}">
                    Back
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body">
            <div class="row">
                @include('penalties.show_fields')
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('penalties.edit', $penalty->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('penalties.viewvoucher', $penalty->id) }}" class="btn btn-success">
                <i class="fas fa-receipt"></i> View Voucher
            </a>
            @if($penalty->status != 'paid')
            <button class="btn btn-primary mark-paid-btn" data-id="{{ $penalty->id }}">
                <i class="fas fa-check"></i> Mark as Paid
            </button>
            @endif
            <a href="{{ route('penalties.delete', $penalty->id) }}" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to delete this penalty entry?')">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.mark-paid-btn').click(function() {
            var penaltyId = $(this).data('id');

            if (confirm('Mark this penalty as paid?')) {
                $.ajax({
                    url: "{{ route('penalties.markpaid', '') }}/" + penaltyId,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        payment_account_id: 1001 // Default cash account
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error marking penalty as paid');
                    }
                });
            }
        });
    });
</script>
@endsection