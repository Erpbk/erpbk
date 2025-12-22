{{-- Include Column Control Panel --}}
@include('components.column-control-panel', [
'tableColumns' => $tableColumns,
'exportRoute' => 'sims.export',
'tableIdentifier' => 'sims_table'
])


<!-- Statistics Section - Will stick with headers -->
<div class="sticky-table-header">
  <div class="sticky-statistics">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0 fw-bold col-md-9">SIM DETAILS</h4>
        <a class=" col-md-1" style="align-content:flex-end" data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);">
          <i class="fa fa-search"></i>
        </a>
        <div class="action-buttons d-flex justify-content-end" >
            <div class="action-dropdown-container">
                <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                    <i class="ti ti-plus"></i>
                    <span>Add Sim</span>
                    <i class="ti ti-chevron-down"></i>
                </button>
                <div class="action-dropdown-menu" id="addBikeDropdown">
                    @can('sim_create')
                    <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Add New Sim" data-action="{{ route('sims.create') }}">
                        <i class="ti ti-plus"></i>
                        <div>
                            <div class="action-dropdown-item-text">Add Sim</div>
                            <div class="action-dropdown-item-desc">Add a new Sim to the system</div>
                        </div>
                    </a>
                    @endcan
                    @can('sim_create')
                    <a class="action-dropdown-item" href="{{ route('sims.import') }}">
                        <i class="ti ti-file-upload"></i>
                        <span>Import Sim Data</span>
                    </a>
                    @endcan
                    @can('sim_view')
                    <a class="action-dropdown-item" href="{{ route('sims.export')}}" data-size="xl" data-title="Export Vehicles" data-action="{{ route('bikes.export') }}">
                        <i class="ti ti-file-export"></i>
                        <span>Export Sim Data</span>
                    </a>
                    <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);" data-size="sm" data-title="Column Control">
                        <i class="ti ti-columns"></i>
                        <div>
                            <div class="action-dropdown-item-text">Column Control</div>
                            <div class="action-dropdown-item-desc">Open column control modal</div>
                        </div>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
      </div>
      <div class="totals-cards">
        <div class="total-card total-sims">
          <div class="label"><i class="fa fa-sim-card"></i>Total Sims</div>
          <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
        </div>
        <div class="total-card total-active">
          <div class="label"><i class="fa fa-check-circle"></i>Active</div>
          <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
        </div>
        <div class="total-card total-inactive">
          <div class="label"><i class="fa fa-times-circle"></i>Inactive</div>
          <div class="value" id="total_rejected">{{ $stats['inactive'] ?? 0 }}</div>
        </div>
        <div class="total-card total-du">
          <div class="label"><i class="fa fa-building"></i>Du Sims</div>
          <div class="value" id="total_hours">{{ $stats['du'] ?? 0 }}</div>
        </div>
        <div class="total-card total-etisalat">
          <div class="label"><i class="fa fa-building"></i>Etisalat Sims</div>
          <div class="value" id="total_hours">{{ $stats['etisalat'] ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Table with Scroll - SINGLE TABLE for both headers and body -->
<div class="table-scroll-container">
  <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
      <tr role="row">
        <th title="Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Number: activate to sort column ascending">Number</th>
        <th title="Company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Company: activate to sort column ascending">Company</th>
        <th title="Emi" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Emi: activate to sort column ascending">Emi</th>
        <th title="Rider ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider ID: activate to sort column ascending">Rider ID</th> 
        <th title="Rider Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider Name: activate to sort column ascending">Rider Name</th> 
        <th title="Vendor" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Vendor: activate to sort column ascending">Vendor</th> 
        <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th> 
        <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $r)
      <tr class="text-center">
        <td>
          <a href="{{ route('sims.show', $r->id) }}" class="table-link">
            {{$r->number}}
          </a>
        </td>
        <td>{{$r->company}}</td>
        <td>{{$r->emi}}</td>
        <td>
          @if($r->assign_to)
            {{$r->riders->rider_id}}
          @else
            -
          @endif
        </td>
        <td>
          @if($r->assign_to)
            {{$r->riders->name}}
          @else
            -
          @endif
        </td>
        <td>
          @if($r->vendors)
            {{$r->vendors->name}}
          @else
            -
          @endif
        </td>
        <td>
          @if($r->status === null)
            <span class="badge bg-secondary">Unknown</span>
          @elseif($r->status)
            <span class="badge bg-success" style="font-size: 0.8rem;">Active</span>
          @else
            <span class="badge bg-danger">Inactive</span>
          @endif
        </td>
        <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @can('sim_assign_edit')
                     @if(!$r->assign_to)
                        <a href="javascript:void(0);" data-size="lg" data-title="Assign Sim" data-action="{{ route('sims.assign', $r->id) }}" class='show-modal dropdown-item waves-effect'>
                           <i class="fa fa-motorcycle my-1"></i>Assign
                        </a>
                     @else
                        <a href="javascript:void(0);" data-size="lg" data-title="Return Sim" data-action="{{ route('sims.return', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                           <i class="fa fa-undo my-1"></i>Return
                        </a>
                     @endif
                  @endcan
                  @can('sim_edit')
                  <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Edit Sim" data-action="{{ route('sims.edit', $r->id) }} ">
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('sim_delete')
                  <a href="javascript:void(0);" class='dropdown-item waves-effect' onclick="confirmDelete()" data-action="{{ route('sims.delete', $r->id) }}">
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if(method_exists($data, 'links'))
  {!! $data->links('components.global-pagination') !!}
@endif
