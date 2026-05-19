@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
      <tr role="row">
         @php
         $tableCols = $tableColumns ?? [];
         $dataColumns = array_values(array_filter($tableCols, function ($c) {
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
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">&nbsp;</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $employee)
      <tr class="text-center">
         @foreach($dataColumns as $col)
         @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
         @switch($key)
         @case('name')
         <td class="text-start"><a href="{{ route('employees.show', $employee->id) }}">{{ $employee->name }}</a></td>
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
            <br><small class="text-muted">{{ $employee->doj->diffForHumans() }}</small>
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
            <span class="badge bg-label-danger">Visa Expired</span>
            @elseif($visaExpiry->diffInDays($today) <= 30)
            <span class="badge bg-label-warning">Visa: {{ $visaExpiry->diffInDays($today) }} days</span>
            @else
            <span class="badge bg-label-success">Visa: {{ $visaExpiry->diffInDays($today) }} days</span>
            @endif
            @endif
            @if(!$emirateExpiry && !$passportExpiry && !$visaExpiry)
            <span class="badge bg-label-secondary">No Documents</span>
            @endif
         </td>
         @break
         @case('status')
         <td class="text-center">
            @include('employees._status_badges', ['status' => $employee->status])
         </td>
         @break
         @case('action')
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $employee->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $employee->id }}" style="z-index: 1050;">
                  @can('employees_view')
                  <a href="{{ route('employees.show', $employee->id) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-eye my-1"></i> View
                  </a>
                  @endcan
                  @can('employees_edit')
                  <a href="{{ route('employees.edit', $employee) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('employees_documents')
                  <a href="javascript:void(0);" data-action="{{ route('employee.files', $employee->id) }}" data-size="xl" data-title="Documents - {{ $employee->name }}" class="dropdown-item waves-effect show-modal">
                     <i class="fa fa-file my-1"></i> Documents
                  </a>
                  @endcan
                  @can('employees_view')
                  <a href="{{ route('employee.ledger', $employee->id) }}" class="dropdown-item waves-effect">
                     <i class="fa fa-book my-1"></i> Ledger
                  </a>
                  @endcan
                  @can('employees_delete')
                  <a href="javascript:void(0);" onclick="confirmDeleteEmployee('{{ route('employees.destroy', $employee) }}')" class="dropdown-item waves-effect">
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
         <td></td>
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
