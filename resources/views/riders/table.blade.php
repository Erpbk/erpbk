@php
$tableCols = $tableColumns ?? [];
$dataColumns = array_values(array_filter($tableCols, function ($c) {
    $k = $c['data'] ?? ($c['key'] ?? null);
    return $k !== 'search'
        && $k !== 'control'
        && $k !== 'action'
        && !in_array($k, ['company_id', 'account_id'], true)
        && field_visible('rider', $k);
}));
$favoriteIds = collect($favoriteRiderIds ?? (auth()->user()->favorite_rider_ids ?? []))->map(fn ($id) => (int) $id)->all();
$ajaxFragment = $ajaxFragment ?? false;
@endphp
@if(!$ajaxFragment)
<table class="table" id="dataTableBuilder">
   <thead>
      <tr id="rlHead">
         @foreach($dataColumns as $col)
         @php
         $title = $col['title'] ?? ($col['name'] ?? ($col['data'] ?? ''));
         $key = $col['data'] ?? ($col['key'] ?? '');
         @endphp
         <th title="{{ $title }}" class="sorting" data-k="{{ $key }}" tabindex="0">{{ $title }}<span class="ar">⇅</span></th>
         @endforeach
         <th class="nosort" tabindex="0">Actions</th>
      </tr>
   </thead>
   <tbody id="rlBody">
@endif
      @forelse($data as $r)
      @php
      $riderPendingDeletion = record_is_pending_deletion($r);
      $isFav = in_array((int) $r->id, $favoriteIds, true);
      @endphp
      <tr class="text-center {{ $riderPendingDeletion ? 'table-warning' : '' }}" data-rider-id="{{ $r->id }}">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('name')
         <td class="name text-start">
            <button type="button" class="rl-star{{ $isFav ? ' on' : '' }}" data-fav="{{ $r->id }}" aria-label="{{ $isFav ? 'Remove from' : 'Add to' }} favorites">★</button>
            <a href="{{ route('riders.show', $r->id) }}">{{ $r->name }}</a>
            @include('delete_requests._pending_badge', ['model' => $r])
         </td>
         @break
         @case('contact_number')
         @case('company_contact')
         @php
         $phone = preg_replace('/[^0-9]/', '', (string) ($r->company_contact ?? ''));
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
         <td>{{ $r->customer?->name ?? '-' }}</td>
         @break
         @case('designation')
         <td>{{ $r->resolvedDesignation() }}</td>
         @break
         @case('dob')
         <td>{{ $r->dob ? \App\Helpers\General::DateFormat($r->dob) : '-' }}</td>
         @break
         @case('doj')
         <td>{{ $r->doj ? \App\Helpers\General::DateFormat($r->doj) : '-' }}</td>
         @break
         @case('branch_id')
         <td>{{ $r->branch ? $r->branch->name . ' (' . $r->branch->code . ')' : '-' }}</td>
         @break
         @case('recruiter_id')
         <td>{{ optional(company_table('recruiters')->where('id', $r->recruiter_id)->first())->name ?? '-' }}</td>
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
         @php
         // bikes() is hasOne — eager load returns a model or null, not a collection
         $bike = $r->relationLoaded('bikes')
            ? $r->bikes
            : company_table('bikes')->where('rider_id', $r->id)->first();
         @endphp
         <td>{{ $bike ? ($bike->plate ?? '-') : '-' }}</td>
         @break
         @case('status')
         <td class="text-center">
            @include('riders._status_badges', [
            'employmentStatus' => data_get($r, 'status'),
            'optionText' => data_get($r, 'rider_status'),
            'statusDays' => data_get($r, 'employment_status_days'),
            'statusChangedAt' => data_get($r, 'last_employment_status_change_date'),
            'wrapperClass' => 'rl-status-wrap',
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
         @default
         <td>
            @php
            $value = data_get($r, $key, '-');
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
         <td>
            @if($riderPendingDeletion)
            @include('delete_requests._pending_badge', ['model' => $r])
            @else
            <div class="dropdown">
               <button class="rl-dots" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Row actions">⋯</button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}">
                  @include('layouts.partials.module_contract_action', [
                  'module' => 'riders',
                  'recordId' => $r->id,
                  'recordLabel' => $r->name . ' (' . $r->rider_id . ') — Contracts',
                  ])
                  @can('riders_rider_edit')
                  <a href="{{ route('riders.edit', ['company_slug' => request()->route('company_slug'), 'rider' => $r->id]) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('riders_rider_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{ route('rider.delete', ['company_slug' => request()->route('company_slug'), 'id' => $r->id]) }}')" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
            @endif
         </td>
      </tr>
      @empty
      @php
      $hasSupervisorOrStatusFilter = request()->filled('fleet_supervisor')
      || request()->filled('rider_status')
      || request()->filled('status');
      @endphp
      <tr>
         <td colspan="{{ count($dataColumns) + 1 }}" class="rl-empty text-center">
            {{ $hasSupervisorOrStatusFilter
            ? 'This rider does not belong to this supervisor or status.'
            : 'No riders match this search.' }}
         </td>
      </tr>
      @endforelse
@if(!$ajaxFragment)
   </tbody>
</table>
@endif

@include('delete_requests._pending_table_script', ['items' => $data])
