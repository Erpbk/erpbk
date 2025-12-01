<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Transaction Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $cod)
            <tr>
                <td>{{ $cod->id }}</td>
                <td>{{ App\Helpers\General::DateFormat($cod->transaction_date) }}</td>
                <td>AED {{ number_format($cod->amount, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $cod->status == 'paid' ? 'success' : ($cod->status == 'unpaid' ? 'danger' : 'warning') }}">
                        {{ ucfirst($cod->status) }}
                    </span>
                </td>
                <td>{{ Str::limit($cod->description, 50) }}</td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="{{ route('cod.show', $cod->id) }}" class="btn btn-info btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('cod.edit', $cod->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="{{ route('cod.viewvoucher', $cod->id) }}" class="btn btn-success btn-sm" title="View Voucher">
                            <i class="fas fa-receipt"></i>
                        </a>
                        @if($cod->status != 'paid')
                        <button class="btn btn-primary btn-sm mark-paid-btn" data-id="{{ $cod->id }}" title="Mark as Paid">
                            <i class="fas fa-check"></i>
                        </button>
                        @endif
                        <a href="{{ route('cod.delete', $cod->id) }}" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this COD entry?')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} results
    </div>
    <div>
        {{ $data->links() }}
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