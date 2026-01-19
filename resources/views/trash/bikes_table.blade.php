@push('third_party_stylesheets')
<style>
   #dataTableBuilder {
      margin-bottom: 0;
      min-width: 800px; 
      width: 100%;
   }

   #dataTableBuilder td,
   #dataTableBuilder th {
      white-space: nowrap;
      padding: 8px 12px;
      vertical-align: middle;
   }
</style>
@endpush

<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
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
         <th title="Deleted At" class="sorting" tabindex="0" rowspan="1" colspan="1">Deleted At</th>
         <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1">Actions</th>
      </tr>
   </thead>
   <tbody>
      @if(isset($trashedRecords) && count($trashedRecords) > 0)
      @foreach($trashedRecords as $item)
      @php
         $r = $item['record'];
         $tableCols = $tableColumns ?? [];
         $dataColumns = array_values(array_filter($tableCols, function($c){
         $k = $c['data'] ?? ($c['key'] ?? null);
         return $k !== 'search' && $k !== 'control';
         }));
      @endphp
      <tr class="text-center">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('bike_code')
         <td tabindex="0">{{ $r->bike_code }}</td>
         @break
         @case('plate')
         <td tabindex="0" class="text-start"><a href="{{ route('bikes.show', $r->id) }}">{{ $r->plate }}</a></td>
         @break
         @case('rider_id')
         @php
         $rider = DB::table('riders')->where('id', $r->rider_id)->first();
         @endphp
         <td tabindex="0">{{ $rider->rider_id ?? '-' }}</td>
         @break
         @case('rider_name')
         @php
         $rider = DB::table('riders')->where('id', $r->rider_id)->first();
         @endphp
         <td tabindex="0">
            @if ($rider)
            <a href="{{ route('riders.show', $rider->id) }}">{{ $rider->name }}</a>
            @else
            -
            @endif
         </td>
         @break
         @case('emirates')
         <td tabindex="0">{{ $r->emirates }}</td>
         @break
         @case('company')
         @php
         $company = DB::Table('leasing_companies')->where('id' , $r->company)->first();
         @endphp
         <td tabindex="0">{{ $company ? $company->name : '-' }}</td>
         @break
         @case('customer_id')
         <td tabindex="0">{{ DB::table('customers')->where('id' , $r->customer_id)->first()->name ?? '-' }}</td>
         @break
         @case('expiry_date')
         <td tabindex="0">{{ $r->expiry_date ? \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') : '-' }}</td>
         @break
         @case('warehouse')
         <td tabindex="0">
            @php
            $bike_warehouse = DB::table('bike_histories')->where('bike_id', $r->id)->first();
            $badgeClass = match($bike_warehouse->warehouse ?? 'Inactive') {
            'Active' => 'bg-label-success',
            'Return' => 'bg-label-warning',
            'Vacation' => 'bg-label-info',
            'Absconded' => 'bg-label-danger',
            'Inactive' => 'bg-label-danger',
            };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $bike_warehouse->warehouse ?? 'Inactive' }}</span>
         </td>
         @break
         @case('status')
         <td tabindex="0">
            @php
            $statusText = $r->status == 1 ? 'Active' : 'Inactive';
            $badgeClass = $r->status == 1 ? 'bg-label-success' : 'bg-label-danger';
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
         </td>
         @break
         @default
         <td tabindex="0">{{ $r->$key ?? '-' }}</td>
         @break
         @endswitch
         @endforeach
         <td>
            <small class="text-muted">
               <i class="fa fa-clock-o"></i> {{ $item['deleted_at']->format('d M Y h:i A') }}
            </small>
            <br>
            <small class="text-danger">
               {{ $item['deleted_at']->diffForHumans() }}
            </small>
         </td>
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $item['id'] }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $item['id'] }}" style="z-index: 1050;">
                  @if($item['can_restore'])
                  <a href="javascript:void(0);" class="dropdown-item waves-effect restore-item" data-form-id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}">
                     <i class="fa fa-undo text-success my-1"></i> Restore
                  </a>
                  <form id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route('trash.restore', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                     @csrf
                  </form>
                  @endif

                  @if($item['can_force_delete'])
                  <a href="javascript:void(0);" class="dropdown-item waves-effect delete-item" data-form-id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}">
                     <i class="fa fa-trash-o text-danger my-1"></i> Delete Forever
                  </a>
                  <form id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route('trash.force-destroy', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                     @csrf
                     @method('DELETE')
                  </form>
                  @endif
               </div>
            </div>
         </td>
      </tr>
      @endforeach
      @else
      <tr>
         <td colspan="{{ count($dataColumns) + 2 }}" class="text-center">
            <div class="py-4">
               <i class="fa fa-info-circle text-muted"></i>
               <p class="text-muted mb-0">No deleted bikes found</p>
            </div>
         </td>
      </tr>
      @endif
   </tbody>
</table>

@if(isset($totalPages) && $totalPages > 1)
<div class="pagination-wrapper">
   <nav>
      <ul class="pagination justify-content-center mb-0">
         <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage - 1])) }}">
               Previous
            </a>
         </li>

         @for($i = 1; $i <= $totalPages; $i++)
            <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
            <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $i])) }}">
               {{ $i }}
            </a>
            </li>
            @endfor

            <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
               <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage + 1])) }}">
                  Next
               </a>
            </li>
      </ul>
   </nav>
   <p class="text-center text-muted mt-2 mb-0">
      Showing {{ count($trashedRecords) }} of {{ $totalCount }} deleted records
   </p>
</div>
@endif

<script>
   $(document).ready(function() {
      var attempts = 0;
      var maxAttempts = 10;

      function tryInitialize() {
         attempts++;

         if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
               try {
                  return new bootstrap.Dropdown(dropdownToggleEl);
               } catch (e) {
                  return null;
               }
            }).filter(Boolean);
         } else if (attempts < maxAttempts) {
            setTimeout(tryInitialize, 100);
         }
      }

      setTimeout(tryInitialize, 100);
   });
</script>

