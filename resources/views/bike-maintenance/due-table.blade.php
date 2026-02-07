@extends('bike-maintenance.index')

@section('page-content')
@if(isset($data) && count($data) > 0)
    <table class="table table-hover dataTable" id="dataTableBuilder">
        <thead>
            <tr class="table-warning">
                <th>Plate Number</th>
                <th>Rider</th>
                <th>Previous KM</th>
                <th>Current KM</th>
                <th>KM Per Maintenance</th>
                <th>Remaining KM</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $bike)
                @php
                    $kmSinceMaintenance = $bike->current_km - $bike->previous_km;
                    $kmRemaining = max(0, $bike->maintenance_km - $kmSinceMaintenance);
                    $percentage = ($kmSinceMaintenance / $bike->maintenance_km) * 100;
                    
                    // Determine severity based on percentage
                    if($percentage >= 90) {
                        $severityClass = 'bg-danger';
                        $rowClass = 'table-warning';
                    } else{
                        $severityClass = 'bg-warning';
                        $rowClass = '';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <a href="{{ route('bikes.show', $bike->id) }}" target="_blank">
                            <strong>{{ $bike->plate ?? 'N/A' }}</strong>
                        </a>
                    </td>
                    <td>
                        @if($bike->rider)
                            {{ $bike->rider->rider_id ?? '' }} - {{ $bike->rider->name ?? '' }}
                            <br>
                            <small class="text-muted">{{ $bike->rider->company_contact ?? '' }}</small>
                        @else
                            <span class="text-muted">No Rider</span>
                        @endif
                    </td>
                    <td>
                        {{ number_format($bike->previous_km) }} km
                    </td>
                    <td>
                        {{ number_format($bike->current_km) }} km
                    </td>
                    <td>
                        {{ number_format($bike->maintenance_km) }} km
                    </td>
                    <td>
                        <span class="badge {{ $severityClass }} text-white p-2">
                            <i class="fa fa-flag-checkered me-1"></i>
                            {{ number_format($kmRemaining) }} km left
                        </span>
                    </td>
                    <td style="position: relative;">
                        <div class="dropdown">
                            <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect bg-white" 
                                    type="button" 
                                    id="actiondropdown_{{ $bike->id }}" 
                                    data-bs-toggle="dropdown" 
                                    aria-haspopup="true" 
                                    aria-expanded="false" 
                                    style="visibility: visible !important; display: inline-block !important;">
                                <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $bike->id }}" style="z-index: 1050;">
                                @can('bike_edit')
                                <a href="javascript:void(0);" 
                                   data-size="md" 
                                   data-title="Update Bike Reading" 
                                   data-action="{{ route('bike-maintenance.editForm', $bike) }}" 
                                   class='show-modal dropdown-item waves-effect'>
                                    <i class="fa fa-motorcycle my-1"></i>Update Bike Reading
                                </a>
                                <a href="javascript:void(0);" 
                                   class='dropdown-item waves-effect show-modal' 
                                   data-size="xl" 
                                   data-title="Add Maintenance Record" 
                                   data-action="{{ route('bikeMaintenance.create') }}?id={{ $bike->id }}">
                                    <i class="fa fa-calendar-check my-1"></i>Add Maintenance Record
                                </a>
                                @endcan
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="text-center py-5">
        <i class="fa fa-check-circle fa-4x text-success mb-3"></i>
        <h4>No Bikes Due For Maintenance Soon</h4>
        <p class="text-muted">All bikes are within their maintenance schedule limits</p>
    </div>
@endif
@endsection