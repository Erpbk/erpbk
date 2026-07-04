@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
      <tr role="row">
         @php
         $tableCols = $tableColumns ?? [];
         $dataColumns = array_values(array_filter($tableCols, function($c){
         $k = $c['data'] ?? ($c['key'] ?? null);
         return $k !== 'search'
         && $k !== 'control'
         && !in_array($k, ['company_id', 'account_id'], true);
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
         @case('contact_number')
         @php
         $phone = preg_replace('/[^0-9]/', '', $r->sim?->number ?? '');
         if (strpos($phone, '971') === 0) { $whatsappNumber = '+' . $phone; $displayNumber = '0' . substr($phone, 3); }
         else { $whatsappNumber = '+971' . ltrim($phone, '0'); $displayNumber = '0' . ltrim($phone, '0'); }
         @endphp
         <td>
            @if ($phone)
            <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="text-success">{{ $displayNumber }}</a>
            @else N/A @endif
         </td>
         @break
         @case('customer_id')
         <td>{{ company_table('customers')->where('id' , $r->customer_id)->first()->name ?? '-'}}</td>
         @break
         @case('branch_id')
         <td>{{ $r->branch ? $r->branch->name . ' (' . $r->branch->code . ')' : '-' }}</td>
         @break
         @case('recruiter_id')
         <td>{{ company_table('recruiters')->where('id' , $r->recruiter_id)->first()->name ?? '-'}}</td>
         @break
         @case('nationality')
         <td>{{ $r->nationality ? (company_table('countries')->where('id', $r->nationality)->value('name') ?? '-') : '-' }}</td>
         @break
         @case('account_id')
         @php
         $account = $r->account_id ? company_table('accounts')->where('id', $r->account_id)->first() : null;
         @endphp
         <td>{{ $account ? ($account->account_code ?? $account->name ?? $r->account_id) : '-' }}</td>
         @break
         @case('bike')
         @php $bike = company_table('bikes')->where('rider_id', $r->id)->first(); @endphp
         <td>{{ $bike ? $bike->plate : '-' }}</td>
         @break
         @case('status')
         <td class="text-center">
            @include('riders._status_badges', [
            'employmentStatus' => data_get($r, 'status'),
            'optionText' => data_get($r, 'rider_status', ''),
            ])
         </td>
         @break
         @case('attendance')
         @php
         $rider = company_table('riders')->find($r->id);
         $timeline = company_table('job_status')->select('id')->where('RID', $r->id)->whereDate('created_at', today())->first();
         $emails = company_table('rider_emails')->select('id')->where('rider_id', $r->id)->whereDate('created_at', today())->first();
         @endphp
         <td>
            @if($timeline)
            <a href="{{ route('rider.timeline') }}/{{ $rider->id }}"><span class="text-danger cursor-pointer" title="Timeline Added">●</span></a>&nbsp;
            @endif
            @if($emails)
            <a href="{{ route('rider.emails') }}/{{ $rider->id }}"><span class="text-success cursor-pointer" title="Email Sent">●</span></a>&nbsp;
            @endif
            <a href="javascript:void(0);" data-action="{{ route('rider.job_status', ['company_slug' => request()->route('company_slug'), 'id' => $rider->id]) }}" data-size="md" data-title="Add Timeline" class="show-modal">{{ $r->attendance }}</a>
         </td>
         @break
         @case('orders_sum')
         @php
         $rider_sum = company_table('rider_activities')->where('d_rider_id', $r->rider_id)->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('delivered_orders');
         @endphp
         <td>{{ $rider_sum ? $rider_sum : '-' }}</td>
         @break
         @case('days')
         @php
         $days = company_table('rider_activities')->where('d_rider_id', $r->rider_id)->where('delivery_rating', 'Yes')->whereMonth('date', now()->month)->whereYear('date', now()->year)->count('date');
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
                  @include('layouts.partials.module_contract_action', [
                    'module' => 'riders',
                    'recordId' => $r->id,
                    'recordLabel' => $r->name . ' (' . $r->rider_id . ') — Contracts',
                  ])
                  @can('rider_edit')
                  <a href="{{ route('riders.edit', ['company_slug' => request()->route('company_slug'), 'rider' => $r->id]) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('rider_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{ route('rider.delete', ['company_slug' => request()->route('company_slug'), 'id' => $r->id]) }}')" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
         @break
         @default
         <td>
            @php
            $value = data_get($r, $key, '-');
            // Blade escapes output via htmlspecialchars(). If $value is an array/collection,
            // convert it into a displayable string first to avoid "must be of type string" errors.
            if (is_array($value)) {
            $value = array_is_list($value)
            ? implode(', ', array_map(fn($v) => is_scalar($v) ? (string)$v : json_encode($v), $value))
            : json_encode($value);
            } elseif ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->toArray();
            $value = array_is_list($value)
            ? implode(', ', array_map(fn($v) => is_scalar($v) ? (string)$v : json_encode($v), $value))
            : json_encode($value);
            }
            @endphp
            {{ $value }}
         </td>
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