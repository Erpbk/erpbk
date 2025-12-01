<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Rider</th>
                <th>Transaction Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $penalty)
            <tr>
                <td>{{ $penalty->id }}</td>
                <td>
                    @if($penalty->rider)
                    <a href="{{ route('penalties.rider', $penalty->rider->id) }}">
                        {{ $penalty->rider->rider_id }} - {{ $penalty->rider->name }}
                    </a>
                    @else
                    N/A
                    @endif
                </td>
                <td>{{ App\Helpers\General::DateFormat($penalty->transaction_date) }}</td>
                <td>AED {{ number_format($penalty->amount, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $penalty->status == 'paid' ? 'success' : ($penalty->status == 'unpaid' ? 'danger' : 'warning') }}">
                        {{ ucfirst($penalty->status) }}
                    </span>
                </td>
                <td>{{ Str::limit($penalty->description, 50) }}</td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="{{ route('penalties.show', $penalty->id) }}" class="btn btn-info btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('penalties.edit', $penalty->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="{{ route('penalties.viewvoucher', $penalty->id) }}" class="btn btn-success btn-sm" title="View Voucher">
                            <i class="fas fa-receipt"></i>
                        </a>
                        @if($penalty->status != 'paid')
                        <button class="btn btn-primary btn-sm mark-paid-btn" data-id="{{ $penalty->id }}" title="Mark as Paid">
                            <i class="fas fa-check"></i>
                        </button>
                        @endif
                        <a href="{{ route('penalties.delete', $penalty->id) }}" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this penalty entry?')" title="Delete">
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