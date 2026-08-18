@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   @php $vf = static fn (string $f): bool => field_visible('bike_rent_company', $f); @endphp
   <thead class="text-center">
      <tr role="row">
         @if($vf('name'))<th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" aria-sort="descending">Name</th>@endif
         @if(($type ?? null) === 'bike_rental')<th title="Type">Type</th>@endif
         @if($vf('company_contact'))<th title="Contact" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact: activate to sort column ascending">Contact</th>@endif
         @if($vf('email'))<th title="Email" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Email: activate to sort column ascending">Email</th>@endif
         <th title="Chart Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Chart Account: activate to sort column ascending">Chart Account</th>
         @if($vf('status'))<th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>@endif
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
       </tr>
   </thead>
   <tbody>
      @foreach($data as $row)
      <tr class="text-center" data-id="{{ $row->id }}">
          @if($vf('name'))<td>
              <a @if($row->customer_type == 'bike_rental')
                    href="{{ route('bikeRentCompanies.bikes', $row->id) }}" 
                    @else
                    href="{{ route('garage_customer.bikes', $row->id) }}"@endif>
                 {{ $row->name }}
              </a>
          </td>@endif
          @if(($type ?? null) === 'bike_rental')
          <td>
              @if($row->party_type === 'individual')
                  <span class="badge bg-info">Individual</span>
              @else
                  <span class="badge bg-secondary">Company</span>
              @endif
          </td>
          @endif
          @if($vf('company_contact'))<td>{{ $row->company_contact }}</td>@endif
          @if($vf('email'))<td>{{ $row->email }}</td>@endif
          <td>
              @if($row->account)
                  {{ $row->account->account_code }} — {{ $row->account->name }}
              @else
                  —
              @endif
          </td>
          @if($vf('status'))<td>
              @if($row->status == 1)
                  <span class="badge bg-success">Active</span>
              @else
                  <span class="badge bg-danger">Inactive</span>
              @endif
          </td>@endif
          <td style="position: relative;">
              <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $row->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                      <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $row->id }}" style="z-index: 1050;">
                      @if(($row->customer_type ?? $type ?? null) === 'garage')
                          @can('garages_customers_edit')
                          <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Update customer" data-action="{{ route('bikeRentCompanies.edit', $row->id) }}">
                              <i class="fa fa-edit my-1"></i> Edit
                          </a>
                          @endcan
                          @can('garages_customers_delete')
                          <a href="javascript:void(0);" onclick="confirmDelete('{{ route('bikeRentCompanies.delete', $row->id) }}')" class="dropdown-item waves-effect">
                              <i class="fa fa-trash"></i> Delete
                          </a>
                          @endcan
                      @else
                          @can('bike_on_rent_customers_edit')
                          <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Update customer" data-action="{{ route('bikeRentCompanies.edit', $row->id) }}">
                              <i class="fa fa-edit my-1"></i> Edit
                          </a>
                          @endcan
                          @can('bike_on_rent_customers_delete')
                          <a href="javascript:void(0);" onclick="confirmDelete('{{ route('bikeRentCompanies.delete', $row->id) }}')" class="dropdown-item waves-effect">
                              <i class="fa fa-trash"></i> Delete
                          </a>
                          @endcan
                      @endif
                  </div>
              </div>
          </td>
       </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._pending_table_script', ['items' => $data])
