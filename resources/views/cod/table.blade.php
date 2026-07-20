<div class="table-responsive">
    @php $vf = static fn (string $f): bool => field_visible('cod', $f); @endphp
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                @if($vf('rider_id'))<th>Rider</th>@endif
                @if($vf('transaction_date'))<th>Transaction Date</th>@endif
                @if($vf('amount'))<th>Amount</th>@endif
                @if($vf('status'))<th>Status</th>@endif
                @if($vf('description'))<th>Description</th>@endif
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $cod)
            <tr>
                <td>{{ $cod->id }}</td>
                @if($vf('rider_id'))<td>
                    @if($cod->rider)
                    <a href="{{ route('cod.rider', $cod->rider->id) }}">
                        {{ $cod->rider->rider_id }} - {{ $cod->rider->name }}
                    </a>
                    @else
                    N/A
                    @endif
                </td>@endif
                @if($vf('transaction_date'))<td>{{ App\Helpers\General::DateFormat($cod->transaction_date) }}</td>@endif
                @if($vf('amount'))<td>{{ \App\Helpers\Currency::format($cod->amount, 2) }}</td>@endif
                @if($vf('status'))<td>
                    <span class="badge badge-{{ $cod->status == 'paid' ? 'success' : ($cod->status == 'unpaid' ? 'danger' : 'warning') }}">
                        {{ ucfirst($cod->status) }}
                    </span>
                </td>@endif
                @if($vf('description'))<td>{{ Str::limit($cod->description, 50) }}</td>@endif
                <td>
                    <div class="btn-group" role="group">
                        @can('cod_view')
                        <a href="{{ route('cod.show', $cod->id) }}" class="btn btn-info btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('cod.viewvoucher', $cod->id) }}" class="btn btn-success btn-sm" title="View Voucher">
                            <i class="fas fa-receipt"></i>
                        </a>
                        @endcan
                        @can('cod_edit')
                        <a href="{{ route('cod.edit', $cod->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($cod->status != 'paid')
                        <button class="btn btn-primary btn-sm mark-paid-btn" data-id="{{ $cod->id }}" title="Mark as Paid">
                            <i class="fas fa-check"></i>
                        </button>
                        @endif
                        @endcan
                        @can('cod_delete')
                        <a href="{{ route('cod.delete', $cod->id) }}" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this COD entry?')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                        @endcan
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