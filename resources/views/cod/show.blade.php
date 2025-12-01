@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>COD Details</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default float-right" href="{{ route('cod.index') }}">
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
                @include('cod.show_fields')
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('cod.edit', $cod->id) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('cod.viewvoucher', $cod->id) }}" class="btn btn-success">
                <i class="fas fa-receipt"></i> View Voucher
            </a>
            @if($cod->status != 'paid')
            <button class="btn btn-primary mark-paid-btn" data-id="{{ $cod->id }}">
                <i class="fas fa-check"></i> Mark as Paid
            </button>
            @endif
            <a href="{{ route('cod.delete', $cod->id) }}" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to delete this COD entry?')">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.mark-paid-btn').click(function() {
            var codId = $(this).data('id');

            if (confirm('Mark this COD as paid?')) {
                $.ajax({
                    url: "{{ route('cod.markpaid', '') }}/" + codId,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        payment_account_id: 1001 // Default cash account
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error marking COD as paid');
                    }
                });
            }
        });
    });
</script>
@endsection