@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" aria-sort="descending">Name</th>
         <th title="Contact" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact: activate to sort column ascending">Contact</th>
         <th title="Email" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Email: activate to sort column ascending">Email</th>
         <th title="Chart Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Chart Account: activate to sort column ascending">Chart Account</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
       </tr>
   </thead>
   <tbody>
      @foreach($data as $row)
      <tr class="text-center">
          <td>
              <a @if($row->customer_type == 'bike_rental')
                    href="{{ route('bikeRentCompanies.files', $row->id) }}" 
                    @else
                    href="{{ route('garage_customer.files', $row->id) }}"@endif>
                 {{ $row->name }}
              </a>
          </td>
          <td>{{ $row->company_contact }}</td>
          <td>{{ $row->email }}</td>
          <td>
              @if($row->account)
                  {{ $row->account->account_code }} — {{ $row->account->name }}
              @else
                  —
              @endif
          </td>
          <td>
              @if($row->status == 1)
                  <span class="badge bg-success">Active</span>
              @else
                  <span class="badge bg-danger">Inactive</span>
              @endif
          </td>
          <td style="position: relative;">
              <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $row->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                      <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $row->id }}" style="z-index: 1050;">
                      @can('bike_edit')
                          <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Update customer" data-action="{{ route('bikeRentCompanies.edit', $row->id) }}">
                              <i class="fa fa-edit my-1"></i> Edit
                          </a>
                      @endcan
                      @can('bike_delete')
                          <a href="javascript:void(0);" onclick="confirmDelete('{{ route('bikeRentCompanies.delete', $row->id) }}')" class="dropdown-item waves-effect">
                              <i class="fa fa-trash"></i> Delete
                          </a>
                      @endcan
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