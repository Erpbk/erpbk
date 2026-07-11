@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" aria-sort="descending">Name</th>
         <th title="Contact Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Number: activate to sort column ascending">Contact Number</th>
         <th title="Balance" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Balance</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"> Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td><a href="{{ route('customer.ledger', $r->id) }}">{{$r->name}}</a><br/></td>
         <td>{{$r->contact_number }}</td>
         @php
            $account = company_table('accounts')->where('id', $r->account_id)->first();
            $account_id = $account->id ?? null;

            $balance = \App\Models\Transactions::where('account_id', $account_id)
                ->select(
                    DB::raw('SUM(debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit')
                )
                ->first();

            $finalBalance = ($balance->total_debit ?? 0) - ($balance->total_credit ?? 0);
        @endphp

        <td>{{ number_format($finalBalance, 2) }}</td>

         <td>
            @if($r->status == 1)
            <span class="badge  bg-success">Active</span>
            @else
            <span class="badge  bg-danger">Inactive</span>
            @endif
         </td>
         
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @can('customers_customer_edit')
                     <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Customer Details" data-action="{{ route('customers.edit', $r->id) }}">
                        <i class="fa fa-edit my-1"></i> Edit
                     </a>
                  @endcan
                  @can('customers_customer_delete')
                  {!! Form::open(['route' => ['customers.destroy', ['id' => $r->id]], 'method' => 'DELETE', 'style' => 'display:inline;']) !!}
                  {!! Form::button('<i class="fa fa-trash"></i> Delete', [
                     'type' => 'submit',
                     'class' => 'dropdown-item waves-effect',
                     'onclick' => 'return confirm("Are you sure you want to delete this customer? This will move it to the Recycle Bin.")'
                  ]) !!}
                  {!! Form::close() !!}
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