@extends('bikes.view')
@push('third_party_stylesheets')

<style>
    .table-responsive {
        max-height: calc(100vh - 150px);
    }
</style>
@endpush

@section('page_content')
<div class="clearfix"></div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-tools me-2"></i> Maintenance History
        </h5>

        <a class="btn btn-primary btn-sm show-modal"
           href="javascript:void(0)"
           data-size="xl"
           data-title="Add Maintenance Record"
           data-action="{{ route('bikeMaintenance.create') }}?id={{ $bikes->id }}">
            <i class="fas fa-plus me-1"></i> Add Maintenance
        </a>
    </div>

    <div class="card-body table-responsive">
        @include('bike-maintenance.table')
        {{-- @if($records->isEmpty())
            <div class="text-center py-5 text-muted">  
                <i class="fas fa-tools fa-3x mb-3"></i>
                <h6>No Maintenance Records</h6>
            </div>
        @else
            <table class="table table-striped dataTable table-hover no-footer" id="dataTableBuilder">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Previous KM</th>
                        <th>Current KM</th>
                        <th>Overdue</th>
                        <th>Total Cost</th>
                        <th>Attachment</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records->sortByDesc('maintenance_date') as $record)
                    <tr>
                        <td>
                            {{ $record->maintenance_date->format('d/m/Y') }}<br>
                            <small class="text-muted">
                                {{ $record->maintenance_date->diffForHumans() }}
                            </small>
                        </td>

                        <td>{{ number_format($record->previous_km) }}</td>
                        <td>{{ number_format($record->current_km) }}</td>

                        <td>
                            @if($record->overdue_km > 0)
                                <span class="badge bg-danger">
                                    {{ number_format($record->overdue_km,1) }} KM
                                </span>
                            @else
                                <span class="badge bg-success">On Time</span>
                            @endif
                        </td>

                        <td>
                            AED {{ number_format($record->total_cost,2) }}
                        </td>

                        <td>
                            @if($record->attachment)
                                <a href="{{ Storage::url($record->attachment) }}"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        
                        <td style="position: relative;">
                            <div class="dropdown">
                            <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect bg-white" type="button" id="actiondropdown_{{ $record->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                                <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $record->id }}" style="z-index: 1050;">
                                @can('bike_edit')
                                <a href="{{ route('bike-maintenance.invoice', $record) }}" target="_blank" class='show-modal dropdown-item waves-effect'>
                                    <i class="fa fa-motorcycle my-1"></i>View Invoice
                                </a>
                                @endcan
                                @can('bike_edit')
                                <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Add Maintenance Record" data-action="{{ route('bikeMaintenance.edit', $record->id) }} ">
                                    <i class="fa fa-edit my-1"></i>Update Reading
                                </a>
                                @endcan
                                @can('bike_edit')
                                <a href="javascript:void(0);" class='dropdown-item waves-effect delete-record' data-url="{{ route('bikeMaintenance.destroy', $record) }} ">
                                    <i class="fa fa-trash my-1"></i>Delete Record
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif --}}
    </div>
</div>
@endsection
