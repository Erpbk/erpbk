@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
      <tr role="row">
         <th title="Rider ID" class="sorting" tabindex="0" rowspan="1" colspan="1">Rider ID</th>
         <th title="Name" class="sorting" tabindex="0" rowspan="1" colspan="1">Name</th>
         <th title="Company Contact" class="sorting" tabindex="0" rowspan="1" colspan="1">Company Contact</th>
         <th title="Customer" class="sorting" tabindex="0" rowspan="1" colspan="1">Customer</th>
         <th title="Recruiter" class="sorting" tabindex="0" rowspan="1" colspan="1">Recruiter</th>
         <th title="Designation" class="sorting" tabindex="0" rowspan="1" colspan="1">Designation</th>
         <th title="Status" class="sorting" tabindex="0" rowspan="1" colspan="1">Status</th>
         <th title="Deleted At" class="sorting" tabindex="0" rowspan="1" colspan="1">Deleted At</th>
         <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1">Actions</th>
      </tr>
   </thead>
   <tbody>
      @if(isset($trashedRecords) && count($trashedRecords) > 0)
      @foreach($trashedRecords as $item)
      @php
         $r = $item['record'];
      @endphp
      <tr class="text-center">
         <td>{{ $r->rider_id ?? '-' }}</td>
         <td class="text-start">
            <a href="{{ route('riders.show', $r->id) }}" target="_blank">{{ $r->name ?? '-' }}</a>
         </td>
         <td>
            @if($r->company_contact)
            @php
            $phone = preg_replace('/[^0-9]/', '', $r->company_contact);
            if (strpos($phone, '971') === 0) { 
               $whatsappNumber = '+' . $phone; 
               $displayNumber = '0' . substr($phone, 3); 
            } else { 
               $whatsappNumber = '+971' . ltrim($phone, '0'); 
               $displayNumber = '0' . ltrim($phone, '0'); 
            }
            @endphp
            <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="text-success">{{ $displayNumber }}</a>
            @else
            N/A
            @endif
         </td>
         <td>{{ DB::table('customers')->where('id', $r->customer_id)->first()->name ?? '-' }}</td>
         <td>{{ DB::table('recruiters')->where('id', $r->recruiter_id)->first()->name ?? '-' }}</td>
         <td>{{ $r->designation ?? '-' }}</td>
         <td>
            @php
            $hasActiveBike = DB::table('bikes')->where('rider_id', $r->id)->where('warehouse', 'Active')->exists();
            $isWalker = $r->designation === 'Walker';
            
            if ($isWalker) {
               $statusText = 'Active';
               $badgeClass = 'bg-label-success';
            } else {
               $statusText = $hasActiveBike ? 'Active' : 'Inactive';
               $badgeClass = $hasActiveBike ? 'bg-label-success' : 'bg-label-danger';
            }
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
         </td>
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
         <td colspan="9" class="text-center">
            <div class="py-4">
               <i class="fa fa-info-circle text-muted"></i>
               <p class="text-muted mb-0">No deleted riders found</p>
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
   // Initialize Bootstrap dropdowns when this content is loaded
   $(document).ready(function() {
      console.log('Riders trash table content loaded, initializing dropdowns');

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
                  console.warn('Failed to initialize dropdown in table:', e);
                  return null;
               }
            }).filter(Boolean);

            console.log('Dropdowns initialized in riders trash table:', dropdownList.length);
         } else if (attempts < maxAttempts) {
            console.log('Bootstrap not ready in table, retrying...', attempts);
            setTimeout(tryInitialize, 100);
         } else {
            console.warn('Bootstrap dropdown initialization failed in table after', maxAttempts, 'attempts');
         }
      }

      setTimeout(tryInitialize, 100);
   });
</script>

