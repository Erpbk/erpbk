@php
$isConsolidated = $isConsolidated ?? false;
$isAllTab = !empty($isAllTab);
$hideDay = !empty($hideDay) || !$isAllTab; // activities: day stacked under DATE
@endphp
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="ID: activate to sort column ascending">ID</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Fleet Supr" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Fleet Supr">Fleet Supr</th>
         <th title="Project" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Project">Project</th>
         <th title="Status" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Ontime%" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ontime%: activate to sort column ascending">Ontime%</th>
         <th title="HR" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="HR: activate to sort column ascending">HR</th>
         <th title="Rejected" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rejected: activate to sort column ascending">Rejected</th>
         <th title="Orders" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Orders: activate to sort column ascending">{{ $isAllTab ? 'Delivered' : 'Orders' }}</th>
         @if($isAllTab)
         <th title="Valid" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Valid: activate to sort column ascending">Valid</th>
         <th title="Invalid" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Invalid: activate to sort column ascending">Invalid</th>
         <th title="Off" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Off: activate to sort column ascending">Off</th>
         @else
         <th title="Valid Day" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Valid Day: activate to sort column ascending">Valid Day</th>
         @endif
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
      $isRowConsolidated = !empty($r->is_consolidated) || $isAllTab;
      $rider = $r->rider ?? company_table('riders')->where('id', $r->rider_id)->first();

      if ($isRowConsolidated) {
        $validCount = (int) ($r->valid_days_count ?? 0);
        $invalidCount = (int) ($r->invalid_days_count ?? 0);
        $offCount = (int) ($r->off_days_count ?? 0);
        $dayStatus = null;
      } else {
        $orders = (float) ($r->delivered_orders ?? 0);
        $hours = (float) ($r->login_hr ?? 0);
        if ($hours == 0) {
          $dayStatus = 'Off';
        } elseif (($orders >= 5 && $hours >= 10) || $orders >= 10) {
          $dayStatus = 'Valid';
        } else {
          $dayStatus = 'Invalid';
        }
        $validCount = $dayStatus === 'Valid' ? 1 : 0;
        $invalidCount = $dayStatus === 'Invalid' ? 1 : 0;
        $offCount = $dayStatus === 'Off' ? 1 : 0;
      }

      $imageName = trim((string) ($rider->image_name ?? ''));
      $photoUrl = ($imageName !== '' && ! in_array(strtolower($imageName), ['default.png', 'default.jpg'], true))
        ? (storage_url('profile/' . $imageName) ?: null)
        : null;
      $initials = collect(preg_split('/\s+/', trim((string) ($rider->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
      $initials = $initials !== '' ? $initials : 'R';
      @endphp
      <tr class="text-center"
         data-delivered="{{ $r->delivered_orders ?? 0 }}"
         data-rejected="{{ $r->rejected_orders ?? 0 }}"
         data-hours="{{ $r->login_hr ?? 0 }}"
         data-ontime="{{ $r->ontime_orders_percentage ?? 0 }}"
         data-valid="{{ $validCount }}"
         data-invalid="{{ $invalidCount }}"
         data-off="{{ $offCount }}">
         <td class="ra-date">
            @if($isRowConsolidated && !empty($r->date_from) && !empty($r->date_to))
              <span>{{ \Carbon\Carbon::parse($r->date_from)->format('d M Y') }}</span>
              <small>to {{ \Carbon\Carbon::parse($r->date_to)->format('d M Y') }}</small>
            @else
              <span>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
              <small>{{ \Carbon\Carbon::parse($r->date)->format('l') }}</small>
            @endif
         </td>
         <td class="ra-id">{{ $r->d_rider_id }}</td>
         <td class="text-left">
            @if($rider)
            <div class="ra-nmw">
              <div class="ra-av" data-ini="{{ $initials }}">
                <span class="ra-av-ini">{{ $initials }}</span>
                @if($photoUrl)
                  <img src="{{ $photoUrl }}" alt="" loading="lazy"
                       onload="this.parentElement.classList.add('has-img')"
                       onerror="this.remove()">
                @endif
              </div>
              <a href="{{ route('rider.activities', $r->rider_id) }}">{{ $rider->name }}</a>
            </div>
            @else
            -
            @endif
         </td>
         <td>{{ $rider->fleet_supervisor ?? '-' }}</td>
         <td>
            @if($rider)
            {{ optional($rider->customer)->name ?? (company_table('customers')->where('id', $rider->customer_id)->first()->name ?? '-') }}
            @else
            -
            @endif
         </td>
         @php
         $hasActiveBike = $rider ? company_table('bikes')->where('rider_id', $rider->id)->where('warehouse', 'Active')->exists() : false;
         $isWalker = $rider && $rider->designation === 'Walker';
         $employmentStatus = $rider
         ? \App\Models\Riders::employmentStatusDisplay($rider->status ?? null)
         : ['label' => 'Inactive', 'badge' => 'bg-label-secondary'];
         $isEmploymentInactive = $rider && (int) ($rider->status ?? 1) !== 1;

         if ($isEmploymentInactive) {
           $statusText = $employmentStatus['label'];
         } elseif ($isWalker) {
           $statusText = 'Active';
         } else {
           $statusText = $hasActiveBike ? 'Active' : 'Inactive';
         }
         $statusClass = preg_replace('/\s+/', '', $statusText) ?: 'Inactive';
         $statusDaysInfo = $rider
         ? \App\Models\Riders::resolveEmploymentStatusDays($rider)
         : ['days' => null, 'changed_at' => null];
         $statusDaysTitle = !empty($statusDaysInfo['changed_at'])
         ? 'Status changed on ' . \Carbon\Carbon::parse($statusDaysInfo['changed_at'])->format('d M Y')
         : 'Days in current status';
         @endphp
         <td class="text-center">
            <div class="ra-st">
               <span class="{{ $statusClass }}">{{ $statusText }}</span>
               @if($statusDaysInfo['days'] !== null)
               <small title="{{ $statusDaysTitle }}">{{ (int) $statusDaysInfo['days'] }} {{ (int) $statusDaysInfo['days'] === 1 ? 'day' : 'days' }}</small>
               @endif
            </div>
         </td>
         <td>
            @php $ontime = (float) ($r->ontime_orders_percentage ?? 0); @endphp
            @if($ontime)
              <span class="ra-pill {{ $ontime >= 90 ? 'g' : ($ontime >= 70 ? 'a' : 'r') }}">{{ rtrim(rtrim(number_format($ontime, 2), '0'), '.') }}%</span>
            @else
              <span class="ra-pill n">—</span>
            @endif
         </td>
         <td>{{ $r->login_hr }}</td>
         <td>
            @php $rej = (int) ($r->rejected_orders ?? 0); @endphp
            <span class="ra-pill {{ $rej > 0 ? 'r' : 'n' }}">{{ $rej }}</span>
         </td>
         <td><b>{{ $r->delivered_orders }}</b></td>
         @if($isAllTab)
         <td><span class="ra-num-good">{{ $validCount }}</span></td>
         <td><span class="{{ $invalidCount > 0 ? 'ra-num-bad' : '' }}">{{ $invalidCount }}</span></td>
         <td><span class="ra-off-badge">{{ $offCount }}</span></td>
         @else
         <td>
            <span class="ra-vd {{ $dayStatus }}">{{ $dayStatus }}</span>
         </td>
         @endif
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
