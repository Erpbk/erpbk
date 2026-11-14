<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="ID: activate to sort column ascending">ID</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Fleet Supr" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Fleet Supr">Fleet Supr</th>
         <th title="Project" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Project">Project</th>
         <th title="Status" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Attendance" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attendance: activate to sort column ascending">Attendance</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
         $rider = $r->rider ?? company_table('riders')->where('id', $r->rider_id)->first();
         $hours = (float) ($r->login_hr ?? 0);
         $attendance = $hours > 0 ? 'Present' : 'Absent';
         $date = \Carbon\Carbon::parse($r->date);
      @endphp
      <tr class="text-center"
         data-delivered="{{ $r->delivered_orders ?? 0 }}"
         data-rejected="{{ $r->rejected_orders ?? 0 }}"
         data-hours="{{ $hours }}"
         data-ontime="{{ $r->ontime_orders_percentage ?? 0 }}"
         data-valid="{{ $attendance === 'Present' ? 1 : 0 }}"
         data-invalid="0"
         data-off="{{ $attendance === 'Absent' ? 1 : 0 }}">
         <td class="ra-date">
            <span>{{ $date->format('d M Y') }}</span>
            <small>{{ $date->format('l') }}</small>
         </td>
         <td class="ra-id">{{ $r->d_rider_id }}</td>
         <td class="text-left">
            @if($rider)
            @php
              $initials = collect(preg_split('/\s+/', trim((string) $rider->name)))
                ->filter()
                ->take(2)
                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                ->implode('');
              $initials = $initials !== '' ? $initials : 'R';
              $imageName = trim((string) ($rider->image_name ?? ''));
              $photoUrl = ($imageName !== '' && ! in_array(strtolower($imageName), ['default.png', 'default.jpg'], true))
                ? (storage_url('profile/' . $imageName) ?: null)
                : null;
            @endphp
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
            <span class="ra-att {{ $attendance }}">{{ $attendance }}</span>
         </td>
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
