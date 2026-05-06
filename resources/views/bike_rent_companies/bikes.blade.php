@extends('bike_rent_companies.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('bikes_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                <table class="table dataTable no-footer" id="dataTableBuilder">
                <thead class="text-center">
                    <tr role="row">
                        <th title="Code" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Code: activate to sort column ascending">Code</th>
                        <th title="Plate" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate: activate to sort column ascending">Plate</th>
                        <th title="Emirates" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Emirates: activate to sort column ascending">Emirates</th>
                        <th title="Company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Company: activate to sort column ascending">Company</th>
                        <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Model Type</th>
                        <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Chassis</th>
                        <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Engine</th>
                        <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Assign</th>
                        <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Return</th>
                        <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
                        <th title="Expiry Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Expiry Date: activate to sort column ascending">Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bikes as $bike)
                    <tr class="text-center">
                        <td>{{ $bike->bike_code ?? '-' }}</td>
                        <td>{{ $bike->plate }}</td>
                        <td>{{ $bike->emirates ?? '-' }}</td>
                        <td>{{ $bike->leasingCompany?->name ?? '-' }}</td>
                        <td>{{ $bike->model_type ?? '-' }}</td>
                        <td>{{ $bike->chassis_number ?? '-' }}</td>
                        <td>{{ $bike->engine ?? '-'}}</td>
                        @php
                            $history = $bike->history->first();
                        @endphp
                        <td>{{ $history?->note_date?->format('M d Y') ?? '-'}}</td>
                        <td>{{ $history->return_date?->format('M d Y') ?? '-'}}</td>
                        <td>
                            @php
                                $statusText = $history->return_date ? 'Returned' : 'Active';
                                $badgeClass = $history->return_date ? 'bg-label-danger' : 'bg-label-success';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                        </td>
                        <td>
                            @if($bike->expiry_date)
                                {{ \Carbon\Carbon::parse($bike->expiry_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
                </table>

                @if(method_exists($bikes, 'links'))
                    {!! $bikes->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
        @endcan
        @cannot('bikes_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Bikes.</h3> 
            </div>
        @endcannot
    </div>
@endsection
