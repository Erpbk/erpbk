@push('third_party_stylesheets')
@endpush
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

   td:focus,
   th:focus {
      outline: 2px solid #2196f3;
      outline-offset: -2px;
      background: #e3f2fd;
   }

   th {
      white-space: nowrap;
   }

   /* Table header bold and fixed */
   #dataTableBuilder thead th {
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: #f8f9fa;
      box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
   }

   /* Ensure table container is scrollable */
   .table-responsive {
      max-height: calc(100vh - 240px);
      overflow-y: auto;
      overflow-x: auto;
      position: relative;
      -webkit-overflow-scrolling: touch;
   }

   /* Hide scrollbar for Chrome, Safari and Opera */
   .table-responsive::-webkit-scrollbar {
      display: none;
   }

   /* Hide scrollbar for IE, Edge and Firefox */
   .table-responsive {
      -ms-overflow-style: none;
      /* IE and Edge */
      scrollbar-width: none;
      /* Firefox */
   }

   #dataTableBuilder td.bike-notes-cell {
      white-space: pre-wrap;
      min-width: 280px;
      max-width: 420px;
      text-align: left;
      vertical-align: middle;
   }


   @keyframes pulse {
      0% {
         transform: scale(1);
         box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
      }

      70% {
         transform: scale(1.02);
         box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
      }

      100% {
         transform: scale(1);
         box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
      }
   }
</style>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
      <tr role="row">
         @php
         $tableCols = $tableColumns ?? [];
         $hiddenTableColumns = ['company_id', 'rental_company_id', 'current_km', 'bike_owner'];
         $dataColumns = array_values(array_filter($tableCols, function ($c) use ($hiddenTableColumns) {
         $k = $c['data'] ?? ($c['key'] ?? null);
         return $k !== 'search' && $k !== 'control' && !in_array($k, $hiddenTableColumns, true)
         && field_visible('bike', $k);
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
      @php $bikePendingDeletion = record_is_pending_deletion($r); @endphp
      <tr class="text-center {{ $bikePendingDeletion ? 'table-warning' : '' }}">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('bike_code')
         <td tabindex="0">{{ $r->bike_code }}</td>
         @break
         @case('plate')
         <td tabindex="0" class="text-start">
            <a href="{{ route('bikes.show', $r->id) }}">{{ $r->plate }}</a>
            @include('delete_requests._pending_badge', ['model' => $r])
         </td>
         @break
         @case('rider_id')
         @php
         $rider = company_table('riders')->where('id', $r->rider_id)->first();

         @endphp
         <td tabindex="0">{{ $rider->rider_id ?? '-' }}</td>
         @break
         @case('name')
         @if($r->rider_id)
         <td tabindex="0"><a href="{{ route('riders.show', $r->rider_id) }}">{{ $r->rider?->name ?? '-' }}</a></td>
         @elseif($r->rental_company_id)
         <td tabindex="0"><a href="{{ route('bikeRentCompanies.show', $r->rental_company_id) }}">{{ $r->rentalCompany?->name ?? '-' }}</a></td>
         @else
         <td tabindex="0">-</td>
         @endif
         @break
         @case('emirates')
         <td tabindex="0">{{ $r->emirates }}</td>
         @break
         @case('company')
         @php
         $isOwnedBike = strcasecmp((string) ($r->bike_owner ?? ''), 'Owned') === 0;
         if ($isOwnedBike) {
            $companyLabel = trim((string) (\App\Helpers\Common::getSetting('company_name') ?: ''));
            if ($companyLabel === '') {
               $currentCompany = view()->shared('currentCompany');
               $companyLabel = is_object($currentCompany) ? trim((string) ($currentCompany->name ?? '')) : '';
            }
            if ($companyLabel === '') {
               $companyLabel = '-';
            }
         } else {
            $companyLabel = $company ? $company->name : '-';
         }
         @endphp
         <td tabindex="0">{{ $companyLabel }}</td>
         @break
         @case('customer_id')
         <td tabindex="0">{{ company_table('customers')->where('id' , $r->customer_id)->first()->name ?? '-' }}</td>
         @break
         @case('branch_id')
         <td tabindex="0">{{ $r->branch ? $r->branch->name .' ( '. $r->branch->code .' )' : '-' }}</td>
         @break
         @case('notes')
         @php
         $latestNotes = $r->latestHistory?->notes;
         $notesClean = null;
         if ($latestNotes && preg_match('/(?:\*Note:\*|Note:)\s*(.+)$/is', $latestNotes, $noteMatch)) {
            $notesClean = trim(strip_tags(str_replace(['*', '_', '~', '`'], '', $noteMatch[1])));
            $notesClean = $notesClean !== '' ? $notesClean : null;
         }
         @endphp
         <td tabindex="0" class="bike-notes-cell">{{ $notesClean ?: '' }}</td>
         @break
         @case('bike_status')
         <td tabindex="0">
            @include('bikes.partials.road_status_badge', ['bike' => $r])
         </td>
         @break
         @case('created_by')
         <td tabindex="0">{{ $r->created_by ? \App\Models\User::find($r->created_by)->name : '-' }}</td>
         @break
         @case('updated_by')
         <td tabindex="0">{{ $r->updated_by ? \App\Models\User::find($r->updated_by)->name : '-' }}</td>
         @break
         @case('action')
         <td tabindex="0" style="position: relative;">
            @if($bikePendingDeletion)
            @include('delete_requests._pending_badge', ['model' => $r])
            @else
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  <a href="{{ route('bikes.show', $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye my-1"></i>Show Bike
                  </a>
                  @can('bikes_bike_edit')
                  <a href="{{ route('bikes.edit', $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit my-1"></i>Edit
                  </a>
                  @endcan
                  @can('bikes_bike_delete')
                  <a href="javascript:void(0);" data-url="{{ route('bikes.delete', $r->id) }}" class='dropdown-item waves-effect delete-bike'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
            @endif
         </td>
         @break
         @case('expiry_date')
         @php
         $expiryDate = $r->expiry_date? \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') : null;
         $isExpiring = false;
         $isExpired = false;

         if ($expiryDate) {
         $expiry = \Carbon\Carbon::parse($expiryDate);
         $now = \Carbon\Carbon::now();

         if ($expiry->isPast()) {
         $isExpired = true;
         } elseif ($expiry->diffInDays($now) <= 30) {
            $isExpiring=true;
            }
            }
            @endphp
            <td>
            @if($expiryDate)
            @if($isExpired)
            <span class="badge badge-danger" style="animation: pulse 1s infinite; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; border: 2px solid #b91c1c;">
               {{ $expiryDate }}
            </span>
            @elseif($isExpiring)
            <span class="badge badge-warning" style="animation: pulse 1.5s infinite; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; border: 2px solid #d97706;">
               {{ $expiryDate }} (SOON!)
            </span>
            @else
            <span>{{ $expiryDate }}</span>
            @endif
            @else
            <span>-</span>
            @endif
            </td>
            @break
            @default
            <td tabindex="0">{{ data_get($r, $key, '-') }}</td>
            @endswitch
            @endforeach
            <td></td>
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif

<!-- Filter modal removed: using right-side sliding sidebar instead -->
@include('delete_requests._pending_table_script', ['items' => $data])