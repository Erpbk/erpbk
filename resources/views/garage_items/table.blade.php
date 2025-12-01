<div class="table-responsive text-nowrap">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Item Code</th>
                <th>Quantity</th>
                <th>Price/Unit</th>
                <th>Avg Price</th>
                <th>Total Amount</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->item_code }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ number_format($item->avg_price, 2) }}</td>
                <td>{{ number_format($item->total_amount, 2) }}</td>
                <td>{{ $item->supplier ? $item->supplier->name : 'N/A' }}</td>
                <td>
                    @if($item->status == 'In Stock')
                    <span class="badge bg-success">{{ $item->status }}</span>
                    @elseif($item->status == 'Low Stock')
                    <span class="badge bg-warning">{{ $item->status }}</span>
                    @else
                    <span class="badge bg-danger">{{ $item->status }}</span>
                    @endif
                </td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('garage-items.show', $item->id) }}" class="btn btn-info btn-sm" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="{{ route('garage-items.vouchers', $item->id) }}" class="btn btn-secondary btn-sm" title="Vouchers">
                            <i class="ti ti-receipt"></i>
                        </a>
                        <a href="{{ route('garage-items.edit', $item->id) }}" class="btn btn-primary btn-sm" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>
                        <form action="{{ route('garage-items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach

            @if(count($data) == 0)
            <tr>
                <td colspan="9" class="text-center">No garage items found</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>