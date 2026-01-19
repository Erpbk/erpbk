

<!-- Statistics Section - Will stick with headers -->
<div class="sticky-table-header">
  <div class="sticky-statistics">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        {{-- <button style="align-content:flex-end !important" data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);">
          <i class="fa fa-search"></i>
        </button> --}}
        <div></div>
        <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i>  Filter Sims</button>
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
         @php
         $tableCols = $tableColumns ?? [];
         $dataColumns = array_values(array_filter($tableCols, function($c){
         $k = $c['data'] ?? ($c['key'] ?? null);
         return $k !== 'search' && $k !== 'control';
         }));
         @endphp
         @foreach($dataColumns as $col)
         @php $title = $col['title'] ?? ($col['name'] ?? ($col['data'] ?? '')); @endphp
         <th title="{{ $title }}" class="sorting" tabindex="0" rowspan="1" colspan="1">{{ $title }}</th>
         @endforeach
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            &nbsp;
         </th>
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
            <a href="{{ route('riders.show', $r->riders->id) }}" class="table-link">
            {{$r->riders->name}}
            </a>
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
                  <a href="#" class='dropdown-item waves-effect' 
                    onclick="confirmDelete('{{ route('sims.delete', $r->id) }}')">
                    <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
        </td>
        <td> &nbsp;</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @if($data->isEmpty())
   <div class="text-center mt-5">
      <h3>No Sims found</h3> 
   </div>
  @endif
</div>

@if(method_exists($data, 'links'))
  {!! $data->links('components.global-pagination') !!}
@endif
