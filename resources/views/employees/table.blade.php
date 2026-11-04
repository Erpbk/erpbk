@php
$tableCols = $tableColumns ?? [];
$hiddenTableColumns = \App\Models\EmployeeCustomField::removedEmployeeColumns();
$dataColumns = array_values(array_filter($tableCols, function ($c) use ($hiddenTableColumns) {
    $k = $c['data'] ?? ($c['key'] ?? null);
    return $k !== 'search'
        && $k !== 'control'
        && $k !== 'action'
        && !in_array($k, $hiddenTableColumns, true)
        && field_visible('employees', $k);
}));
$favoriteIds = collect($favoriteEmployeeIds ?? (auth()->user()->favorite_employee_ids ?? []))->map(fn ($id) => (int) $id)->all();
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
      @forelse($data as $employee)
      @php $isFav = in_array((int) $employee->id, $favoriteIds, true); @endphp
      <tr class="text-center" data-id="{{ $employee->id }}">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('name')
         <td class="name text-start">
            <button type="button" class="rl-star{{ $isFav ? ' on' : '' }}" data-fav="{{ $employee->id }}" aria-label="{{ $isFav ? 'Remove from' : 'Add to' }} favorites">★</button>
            @php
              $hvInitials = collect(explode(' ', (string) $employee->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
              $hvPhoto = $employee->profile_image_url;
            @endphp
            <a href="{{ route('employees.show', $employee->id) }}"
               class="emp-name-link"
               data-hv-id="{{ $employee->employee_id }}"
               data-hv-name="{{ $employee->name }}"
               data-hv-photo="{{ $hvPhoto }}"
               data-hv-initials="{{ strtoupper($hvInitials) }}">{{ $employee->name }}</a>
         </td>
         @break
         @case('company_contact')
         @php
         $phone = preg_replace('/[^0-9]/', '', (string) $employee->company_contact);
         if (strpos($phone, '971') === 0) {
         $whatsappNumber = '+' . $phone;
         $displayNumber = '0' . substr($phone, 3);
         } else {
         $whatsappNumber = $phone !== '' ? '+971' . ltrim($phone, '0') : '';
         $displayNumber = $phone !== '' ? '0' . ltrim($phone, '0') : '';
         }
         @endphp
         <td>
            @if ($employee->company_contact)
            <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" class="text-success">{{ $displayNumber }}</a>
            @else
            -
            @endif
         </td>
         @break
         @case('branch_id')
         <td>
            @if($employee->branch)
            {{ $employee->branch->name }} ({{ $employee->branch->code }})
            @else
            -
            @endif
         </td>
         @break
         @case('department_id')
         <td>{{ $employee->department->name ?? '-' }}</td>
         @break
         @case('nationality_id')
         <td>{{ $employee->nationality->name ?? (company_table('countries')->where('id', $employee->nationality_id)->value('name') ?? '-') }}</td>
         @break
         @case('doj')
         <td>
            @if($employee->doj)
            {{ $employee->doj->format('d M Y') }}
            @else
            -
            @endif
         </td>
         @break
         @case('documents_expiry')
         <td class="text-start">
            @php
            $today = \Carbon\Carbon::today();
            $emirateExpiry = $employee->emirate_expiry;
            $passportExpiry = $employee->passport_expiry;
            $visaExpiry = $employee->visa_expiry;
            $licenseExpiry = $employee->license_expiry;
            $roadPermitExpiry = $employee->road_permit_expiry;
            $laborCardExpiry = $employee->labor_card_expiry;
            @endphp
            @if($emirateExpiry)
            @if($emirateExpiry->isPast())
            <span class="badge bg-label-danger mb-1 d-block">Emirates ID Expired</span>
            @elseif($emirateExpiry->diffInDays($today) <= 30)
               <span class="badge bg-label-warning mb-1 d-block">Emirates ID: {{ $emirateExpiry->diffInDays($today) }} days</span>
               @else
               <span class="badge bg-label-success mb-1 d-block">Emirates ID: {{ $emirateExpiry->diffInDays($today) }} days</span>
               @endif
               @endif
               @if($passportExpiry)
               @if($passportExpiry->isPast())
               <span class="badge bg-label-danger mb-1 d-block">Passport Expired</span>
               @elseif($passportExpiry->diffInDays($today) <= 60)
                  <span class="badge bg-label-warning mb-1 d-block">Passport: {{ $passportExpiry->diffInDays($today) }} days</span>
                  @else
                  <span class="badge bg-label-success mb-1 d-block">Passport: {{ $passportExpiry->diffInDays($today) }} days</span>
                  @endif
                  @endif
                  @if($visaExpiry)
                  @if($visaExpiry->isPast())
                  <span class="badge bg-label-danger mb-1 d-block">Visa Expired</span>
                  @elseif($visaExpiry->diffInDays($today) <= 30)
                     <span class="badge bg-label-warning mb-1 d-block">Visa: {{ $visaExpiry->diffInDays($today) }} days</span>
                     @else
                     <span class="badge bg-label-success mb-1 d-block">Visa: {{ $visaExpiry->diffInDays($today) }} days</span>
                     @endif
                     @endif
                     @if($licenseExpiry)
                     @if($licenseExpiry->isPast())
                     <span class="badge bg-label-danger mb-1 d-block">License Expired</span>
                     @elseif($licenseExpiry->diffInDays($today) <= 30)
                        <span class="badge bg-label-warning mb-1 d-block">License: {{ $licenseExpiry->diffInDays($today) }} days</span>
                        @else
                        <span class="badge bg-label-success mb-1 d-block">License: {{ $licenseExpiry->diffInDays($today) }} days</span>
                        @endif
                        @endif
                        @if($roadPermitExpiry)
                        @if($roadPermitExpiry->isPast())
                        <span class="badge bg-label-danger mb-1 d-block">Road Permit Expired</span>
                        @elseif($roadPermitExpiry->diffInDays($today) <= 30)
                           <span class="badge bg-label-warning mb-1 d-block">Road Permit: {{ $roadPermitExpiry->diffInDays($today) }} days</span>
                           @else
                           <span class="badge bg-label-success mb-1 d-block">Road Permit: {{ $roadPermitExpiry->diffInDays($today) }} days</span>
                           @endif
                           @endif
                           @if($laborCardExpiry)
                           @if($laborCardExpiry->isPast())
                           <span class="badge bg-label-danger">Labor Card Expired</span>
                           @elseif($laborCardExpiry->diffInDays($today) <= 30)
                              <span class="badge bg-label-warning">Labor Card: {{ $laborCardExpiry->diffInDays($today) }} days</span>
                              @else
                              <span class="badge bg-label-success">Labor Card: {{ $laborCardExpiry->diffInDays($today) }} days</span>
                              @endif
                              @endif
                              @if(!$emirateExpiry && !$passportExpiry && !$visaExpiry && !$licenseExpiry && !$roadPermitExpiry && !$laborCardExpiry)
                              <span class="badge bg-label-secondary">No Documents</span>
                              @endif
         </td>
         @break
         @case('status')
         <td class="text-center">
            @include('employees._status_badges', ['status' => $employee->status])
         </td>
         @break
         @default
         <td>
            @php
            if (is_string($key) && str_starts_with($key, 'custom_field_values.')) {
            $customId = (int) substr($key, strlen('custom_field_values.'));
            $customValues = is_array($employee->custom_field_values) ? $employee->custom_field_values : [];
            $value = $customValues[$customId] ?? $customValues[(string) $customId] ?? '-';
            } else {
            $value = data_get($employee, $key, '-');
            }
            if (is_array($value)) {
            $value = array_is_list($value)
            ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value))
            : json_encode($value);
            } elseif ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->implode(', ');
            } elseif ($value instanceof \Carbon\CarbonInterface) {
            $value = $value->format('d M Y');
            }
            @endphp
            {{ $value }}
         </td>
         @endswitch
         @endforeach
         <td>
            <div class="dropdown">
               <button class="rl-dots" type="button" id="actiondropdown_{{ $employee->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Row actions">⋯</button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $employee->id }}">
                  @include('layouts.partials.module_contract_action', [
                    'module' => 'employees',
                    'recordId' => $employee->id,
                    'recordLabel' => $employee->name . ' (' . $employee->employee_id . ') — Contracts',
                  ])
                  @can('employees_employee_view')
                  <a href="{{ route('employees.show', $employee->id) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-eye my-1"></i> View
                  </a>
                  @endcan
                  @can('employees_employee_edit')
                  <a href="{{ route('employees.edit', $employee) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('employees_document_view')
                  <a href="javascript:void(0);" data-action="{{ route('employee.files', $employee->id) }}" data-size="xl" data-title="Documents - {{ $employee->name }}" class="dropdown-item waves-effect show-modal">
                     <i class="fa fa-file my-1"></i> Documents
                  </a>
                  @endcan
                  @can('employees_ledger_view')
                  <a href="{{ route('employee.ledger', $employee->id) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-book my-1"></i> Ledger
                  </a>
                  @endcan
                  @can('employees_employee_delete')
                  <a href="javascript:void(0);" onclick="confirmDeleteEmployee('{{ route('employees.destroy', $employee) }}')" class="dropdown-item waves-effect">
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="{{ count($dataColumns) + 1 }}" class="rl-empty text-center">
            No employees match this search.
         </td>
      </tr>
      @endforelse
@if(!$ajaxFragment)
   </tbody>
</table>
@endif

@include('delete_requests._pending_table_script', ['items' => $data])
