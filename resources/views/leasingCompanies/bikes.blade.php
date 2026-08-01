@extends('leasing_companies.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }

    .bike-note-cell {
        min-width: 280px;
        max-width: 420px;
        white-space: pre-wrap;
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('leasing_companies_view')
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
                        <th title="Leased Date">Leased Date</th>
                        <th title="Bike Code">Bike Code</th>
                        <th title="Plate">Plate</th>
                        <th title="Emirates">Emirates</th>
                        <th title="Model Type">Model Type</th>
                        <th title="Chassis">Chassis</th>
                        <th title="Engine">Engine</th>
                        <th title="Expiry Date">Expiry Date</th>
                        <th title="Return Date">Return Date</th>
                        <th title="Status">Status</th>
                        <th title="Note">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bikes as $bike)
                    @php
                    $roadStatus = $bike->road_status;
                    $latestNote = '';
                    if ($roadStatus['is_returned']) {
                        $latestNoteRaw = (string) ($bike->leasingReturnHistory?->notes ?? '');
                        if ($latestNoteRaw !== '' && preg_match('/(?:\*Note:\*|Note:)\s*(.+)$/is', $latestNoteRaw, $noteMatch)) {
                            $latestNote = trim(str_replace('*', '', $noteMatch[1]));
                        }
                    }
                    @endphp
                    <tr class="text-center">
                        <td>
                            @if($bike->leased_date)
                                {{ \Carbon\Carbon::parse($bike->leased_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $bike->bike_code ?? '-' }}</td>
                        <td><a href='{{ route('bikes.show', $bike->id) }}' target='_blank'>{{ $bike->plate }}</a></td>
                        <td>{{ $bike->emirates ?? '-' }}</td>
                        <td>{{ $bike->model_type ?? '-' }}</td>
                        <td>{{ $bike->chassis_number ?? '-' }}</td>
                        <td>{{ $bike->engine ?? '-'}}</td>
                        <td>
                            @if($bike->expiry_date)
                                {{ \Carbon\Carbon::parse($bike->expiry_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($bike->leased_return_date)
                                {{ \Carbon\Carbon::parse($bike->leased_return_date)->format('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @include('bikes.partials.road_status_badge', ['bike' => $bike, 'status' => $roadStatus])
                        </td>
                        <td class="bike-note-cell">{{ $latestNote }}</td>
                    </tr>
                    @endforeach
                </tbody>
                </table>

                @if(method_exists($bikes, 'links'))
                    {!! $bikes->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
        @else
            <div class="text-center mt-5">
                <h3>You do not have permission to view Bikes.</h3> 
            </div>
        @endcan
    </div>
@endsection
