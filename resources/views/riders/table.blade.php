@push('third_party_stylesheets')
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
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            &nbsp;
         </th>

      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('name')
         <td class="text-start"><a href="{{ route('riders.show', $r->id) }}">{{ $r->name }}</a><br /></td>
         @break
         @case('company_contact')
         @php
         $phone = preg_replace('/[^0-9]/', '', $r->company_contact);
         if (strpos($phone, '971') === 0) { $whatsappNumber = '+' . $phone; $displayNumber = '0' . substr($phone, 3); }
         else { $whatsappNumber = '+971' . ltrim($phone, '0'); $displayNumber = '0' . ltrim($phone, '0'); }
         @endphp
         <td>
            @if ($r->company_contact)
            <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="text-success">{{ $displayNumber }}</a>
            @else N/A @endif
         </td>
         @break
         @case('customer_id')
         <td>{{ DB::table('customers')->where('id' , $r->customer_id)->first()->name ?? '-'}}</td>
         @break
         @case('recruiter_id')
         <td>{{ DB::table('recruiters')->where('id' , $r->recruiter_id)->first()->name ?? '-'}}</td>
         @break
         @case('l_license')
         <td>{{ ($r->l_license == 1) ? 'Learning' : '-' }}</td>
         @break
         @case('bike')
         @php $bike = DB::table('bikes')->where('rider_id', $r->id)->first(); @endphp
         <td>{{ $bike ? $bike->plate : '-' }}</td>
         @break
         @case('emirate_hub')
         @php $bike = DB::table('bikes')->where('rider_id', $r->id)->first(); @endphp
         <td>{{ $bike && $bike->emirates ? $bike->emirates : '-' }}</td>
         @break
         @case('status')
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
         <td>
            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
         </td>
         @break
         @case('attendance')
         @php
         $rider = DB::Table('riders')->find($r->id);
         $timeline = DB::Table('job_status')->select('id')->where('RID', $r->id)->whereDate('created_at', '=', $r->attendance_date)->first();
         $emails = DB::Table('rider_emails')->select('id')->where('rider_id', $r->id)->whereDate('created_at', '=', $r->attendance_date)->first();
         @endphp
         <td>
            @if($timeline)
            <a href="{{ route('rider.timeline') }}/{{ $rider->id }}"><span class="text-danger cursor-pointer" title="Timeline Added">●</span></a>&nbsp;
            @endif
            @if($emails)
            <a href="{{ route('rider.emails') }}/{{ $rider->id }}"><span class="text-success cursor-pointer" title="Email Sent">●</span></a>&nbsp;
            @endif
            <a href="javascript:void(0);" data-action="{{ url('riders/job_status', $rider->id) }}" data-size="md" data-title="Add Timeline" class="show-modal">{{ $r->attendance }}</a>
         </td>
         @break
         @case('orders_sum')
         @php
         $rider_sum = DB::table('rider_activities')->where('d_rider_id', $r->rider_id)->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('delivered_orders');
         @endphp
         <td>{{ $rider_sum ? $rider_sum : '-' }}</td>
         @break
         @case('days')
         @php
         $days = DB::table('rider_activities')->where('d_rider_id', $r->rider_id)->where('delivery_rating', 'Yes')->whereMonth('date', now()->month)->whereYear('date', now()->year)->count('date');
         @endphp
         <td>{{ $days ? $days : '-' }}</td>
         @break
         @case('balance')
         @php $balance = App\Helpers\Accounts::getBalance($r->account_id); @endphp
         <td>{{ $balance ? $balance : '-' }}</td>
         @break
         @case('action')
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  <a href="javascript:void();" data-action="{{route('rider_contract_upload', $r->id)}}" data-size="md" data-title="{{ $r->name }} ({{ $r->rider_id }}) Contract" class="dropdown-item waves-effect show-modal"><i class="fas fa-file my-1"></i> Contract</a>
                  <a href="javascript:void();" data-action="{{route('rider.sendemail', $r->id)}}" data-size="md" data-title="{{ $r->name }} ({{ $r->rider_id }})" class="dropdown-item waves-effect show-modal"><i class="fas fa-envelope my-1"></i> Send Email</a>
                  @can('rider_edit')
                  <a href="{{ route('riders.edit', $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('rider_delete')
                  <a href="{{ route('rider.delete', $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
         @break
         @default
         <td>{{ data_get($r, $key, '-') }}</td>
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