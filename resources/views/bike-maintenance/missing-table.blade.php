@extends('bike-maintenance.index')
@section('page-content')
@if(isset($data) && count($data) > 0)
    <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
        <thead class="text-center">
            <tr role="row">
                <th>Plate Number</th>
                <th>Missing Fields</th>
                <th>Rider</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $bike)
            <tr class="text-center">
                <td>
                    <a href="{{ route('bikes.show', $bike->id) }}" target="_blank">
                       <strong> {{ $bike->plate ?? 'N/A' }}</strong>
                    </a>
                </td>
                <td>
                    @php
                        $missingFields = [];
                        if (is_null($bike->current_km)) $missingFields[] = 'Current KM';
                        if (is_null($bike->previous_km)) $missingFields[] = 'Previous KM';
                        if (is_null($bike->maintenance_km)) $missingFields[] = 'Maintenance KM';
                    @endphp
                    @foreach($missingFields as $field)
                    <span class="badge bg-danger maintenance-badge">{{ $field }}</span>
                    @endforeach
                </td>
                <td>{{ $bike->rider ? $bike->rider->rider_id .'-'. $bike->rider->name :'No Rider' }}</td>
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
        <h4>No bikes With Missing Relevant Data</h4>
        <p class="text-muted">All bikes are up to date with relevant data.</p>
    </div>
@endif
@endsection