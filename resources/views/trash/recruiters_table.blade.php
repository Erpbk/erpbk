<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" rowspan="1" colspan="1">Name</th>
         <th title="Email" class="sorting" tabindex="0" rowspan="1" colspan="1">Email</th>
         <th title="Contact Number" class="sorting" tabindex="0" rowspan="1" colspan="1">Contact Number</th>
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
         <td>
            <a href="{{ route('recruiters.riders', $r->id) }}">{{$r->name}}</a>
            <br />
         </td>
         <td>{{$r->email }}</td>
         <td>{{$r->contact_number }}</td>
         <td>
            @if($r->status == 1)
            <span class="badge bg-success">Active</span>
            @else
            <span class="badge bg-danger">Inactive</span>
            @endif
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
         <td colspan="6" class="text-center">
            <div class="py-4">
               <i class="fa fa-info-circle text-muted"></i>
               <p class="text-muted mb-0">No deleted recruiters found</p>
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

