@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Garage Items /</span> View
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Garage Item Details</h5>
            <div>
                <a href="{{ route('garage-items.edit', $garageItem->id) }}" class="btn btn-primary">
                    <i class="ti ti-pencil me-1"></i>Edit
                </a>
                <a href="{{ route('garage-items.vouchers', $garageItem->id) }}" class="btn btn-info">
                    <i class="ti ti-receipt me-1"></i>Vouchers
                </a>
                <a href="{{ route('garage-items.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Name</th>
                        <td>{{ $garageItem->name }}</td>
                    </tr>
                    <tr>
                        <th>Item Code</th>
                        <td>{{ $garageItem->item_code }}</td>
                    </tr>
                    <tr>
                        <th>Quantity</th>
                        <td>{{ $garageItem->qty }}</td>
                    </tr>
                    <tr>
                        <th>Price (Per Unit)</th>
                        <td>{{ number_format($garageItem->price, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Average Price (Per Unit)</th>
                        <td>{{ number_format($garageItem->avg_price, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total Amount</th>
                        <td>{{ number_format($garageItem->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Supplier</th>
                        <td>{{ $garageItem->supplier ? $garageItem->supplier->name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($garageItem->status == 'In Stock')
                            <span class="badge bg-success">{{ $garageItem->status }}</span>
                            @elseif($garageItem->status == 'Low Stock')
                            <span class="badge bg-warning">{{ $garageItem->status }}</span>
                            @else
                            <span class="badge bg-danger">{{ $garageItem->status }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Purchase Date</th>
                        <td>{{ $garageItem->purchase_date->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $garageItem->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>Updated At</th>
                        <td>{{ $garageItem->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection