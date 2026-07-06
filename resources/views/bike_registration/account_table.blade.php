<table class="table table-sm table-striped table-hover align-middle mb-0 w-100" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th class="text-start">Plate</th>
         <th>Bike code</th>
         <th class="text-start">Model</th>
         <th class="text-start">Chassis #</th>
         <th>Color</th>
         <th>Warehouse</th>
         <th>Bike status</th>
         <th class="text-start">Traffic file</th>
         <th>Emirates</th>
         <th>Insurance expiry</th>
         <th style="width: 1%; white-space: nowrap;">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
      // Account bike_id first; rider->bikes is hasOne (single model), not a collection.
      $displayBike = $r->bike ?? $r->rider?->bikes;
      $warehouse = $displayBike ? ($displayBike->warehouse ?? null) : null;
      $hasActiveBike = ($warehouse ?? '') === 'Active';
      $badgeClass = $hasActiveBike ? 'bg-label-success' : 'bg-label-danger';
      $bikeStatusLabel = $displayBike ? (string)($displayBike->status ?? '—') : '—';
      $insuranceExpiry = '—';
      if ($displayBike && !empty($displayBike->insurance_expiry)) {
      try {
      $insuranceExpiry = \Carbon\Carbon::parse($displayBike->insurance_expiry)->format('d M Y');
      } catch (\Throwable $e) {
      $insuranceExpiry = (string) $displayBike->insurance_expiry;
      }
      }
      @endphp
      <tr class="text-center">
         <td>
            @if($displayBike)
            <a href="{{ route('BikeRegistration.generatentries', $r->id) }}" class="">{{ $displayBike->plate ?: '—' }}</a>
            @else
            <a href="{{ route('BikeRegistration.generatentries', $r->id) }}" class="text-muted">{{ $r->name ?? '—' }}</a>
            @endif
         </td>
         <td>{{ optional($displayBike)->bike_code ?? '—' }}</td>
         <td class="text-start">{{ optional($displayBike)->model ?? '—' }}</td>
         <td class="text-start small text-break">{{ optional($displayBike)->chassis_number ?? '—' }}</td>
         <td>{{ optional($displayBike)->color ?? '—' }}</td>
         <td>{{ $warehouse ?: '—' }}</td>
         <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $hasActiveBike ? 'Active' : 'Inactive' }}</span> </td>
         <td class="text-center small">{{ optional($displayBike)->traffic_file_number ?? '—' }}</td>
         <td>{{ optional($displayBike)->emirates ?? '—' }}</td>
         <td class="small">{{ $insuranceExpiry }}</td>
         <td class="text-end">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  @can('bike_registration_view')
                  <button type="button" class='dropdown-item waves-effect js-br-expenses-modal border-0 bg-transparent w-100 text-start' data-account-id="{{ $r->id }}" data-url="{{ route('BikeRegistration.generatentries', $r->id) }}">
                     <i class="fa fa-eye"></i> View expenses
                  </button>
                  @endcan
                  @can('bike_registration_edit')
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i> Edit
                  </a>
                  @endcan
                  @can('bike_registration_delete')
                  <a href="javascript:void(0);" data-delete-url="{{ route('BikeRegistration.deleteaccount', $r->id) }}" class='dropdown-item waves-effect js-delete-expense-account'>
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

@foreach($data as $r)
<div class="modal fade" id="editaccount{{ $r->id }}" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Update Account</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <form action="{{ route('BikeRegistration.editaccount') }}" method="POST">
               @csrf
               <input type="hidden" name="id" value="{{ $r->id }}">
               <div class="row">
                  <div class="form-group col-md-12">
                     <label for="rider-{{ $r->id }}">Select Rider</label>
                     <select class="form-control rider-select" id="rider-{{ $r->id }}" name="rider_id">
                        <option value="" selected>Select</option>
                        @foreach(company_table('riders')->where('status' , 1)->get() as $ri)
                        <option value="{{ $ri->id }}" @if($ri->id == $r->rider_id) selected @endif>{{ $ri->rider_id }} - {{ $ri->name }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-12 form-group text-center">
                     <button type="submit" class="btn btn-primary mt-3">Submit</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
@endforeach

<div class="d-flex justify-content-center justify-content-sm-end py-3 px-2">
   {!! $data->links('pagination') !!}
</div>