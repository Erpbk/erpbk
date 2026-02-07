@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr>
         <th colspan="11" class="text-start">
            <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Leasing Company Invoices</h5>
            </div>
         </th>
      </tr>
      <tr role="row">
         <th title="Id" class="sorting" rowspan="1" colspan="1">Id</th>
         <th title="Invoice Number" class="sorting" rowspan="1" colspan="1">Invoice #</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1">Inv Date</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1">Billing Month</th>
         <th title="Leasing Company" class="sorting" rowspan="1" colspan="1">Leasing Company</th>
         <th title="Bikes" class="sorting" rowspan="1" colspan="1">Bikes</th>
         <th title="Subtotal" class="sorting" rowspan="1" colspan="1">Subtotal</th>
         <th title="Vat" class="sorting" rowspan="1" colspan="1">Vat</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Total Amount</th>
         <th title="Attachments" class="sorting" rowspan="1" colspan="1">Attachments</th>
         <th title="Status" class="sorting" rowspan="1" colspan="1">Status</th>
         <th title="Action" width="150px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
      </tr>
   </thead>
   <tbody>
      @forelse($data as $invoice)
      <tr class="text-center">
         <td>{{ $invoice->id }}</td>
         <td>{{ $invoice->invoice_number ?? 'LCI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT) }}</td>
         <td>{{ \Carbon\Carbon::parse($invoice->inv_date)->format('d M Y') }}</td>
         <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
         <td>{{ $invoice->leasingCompany->name ?? '-' }}</td>
         <td>
            @php
            $bikeCount = $invoice->items->count();
            @endphp
            <span class="badge bg-info">{{ $bikeCount }} bike(s)</span>
         </td>
         <td>AED {{ number_format($invoice->subtotal ?? 0, 2) }}</td>
         <td>AED {{ number_format($invoice->vat ?? 0, 2) }}</td>
         <td><strong>AED {{ number_format($invoice->total_amount ?? 0, 2) }}</strong></td>
         <td>
            @if($invoice->attachment)
            <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank">
               <i class="fa fa-file-pdf-o"></i> View Attachment
            </a>
            @endif
         </td>
         <td>
            @if($invoice->status == 1)
            <span class="badge bg-success">Paid</span>
            @else
            <span class="badge bg-danger">Unpaid</span>
            @endif
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown">
                  @can('leasing_company_invoice_view')
                  <a href="{{ route('leasingCompanyInvoices.show', $invoice->id) }}" class='dropdown-item waves-effect' target="_blank">
                     <i class="fa fa-eye mx-1"></i> View
                  </a>
                  @endcan
                  @can('leasing_company_invoice_edit')
                  <a href="javascript:void(0);" data-action="{{ route('leasingCompanyInvoices.edit', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Invoice">
                     <i class="fa fa-edit mx-1"></i> Edit
                  </a>
                  @endcan
                  @can('leasing_company_invoice_create')
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                  <a href="javascript:void(0);" data-action="{{ route('leasingCompanyInvoices.createFromClone', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Clone Invoice (Next Month)">
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                  <a href="javascript:void(0);" onclick="cloneInvoice({{ $invoice->id }})" class='dropdown-item waves-effect'>
>>>>>>> Stashed changes
                     <i class="fa fa-copy mx-1 text-primary"></i> Clone (Next Month)
                  </a>
                  @endcan
                  @can('leasing_company_invoice_delete')
                  {!! Form::open(['route' => ['leasingCompanyInvoices.destroy', $invoice->id], 'method' => 'DELETE', 'style' => 'display:inline;']) !!}
                  {!! Form::button('<i class="fa fa-trash mx-1"></i> Delete', [
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                  'type' => 'submit',
                  'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                  'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
=======
                      'type' => 'submit',
                      'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                      'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
>>>>>>> Stashed changes
=======
                      'type' => 'submit',
                      'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                      'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
>>>>>>> Stashed changes
=======
                      'type' => 'submit',
                      'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                      'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
>>>>>>> Stashed changes
=======
                      'type' => 'submit',
                      'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                      'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
>>>>>>> Stashed changes
                  ]) !!}
                  {!! Form::close() !!}
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="11" class="text-center">No invoices found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
<<<<<<< Updated upstream
@endif
=======
@endif

<script>
   function cloneInvoice(id) {
      Swal.fire({
         title: 'Clone Invoice',
         text: 'This will create a new invoice for the next month with the same bikes and rental amounts. Continue?',
         icon: 'question',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Yes, clone it!'
      }).then((result) => {
         if (result.isConfirmed) {
            $.ajax({
               url: '{{ route("leasingCompanyInvoices.clone", ":id") }}'.replace(':id', id),
               type: 'POST',
               data: {
                  _token: '{{ csrf_token() }}'
               },
               success: function(response) {
                  Swal.fire({
                     title: 'Success!',
                     text: response.message || 'Invoice cloned successfully.',
                     icon: 'success',
                     confirmButtonText: 'OK'
                  }).then(() => {
                     if (response.redirect) {
                        window.location.href = response.redirect;
                     } else {
                        location.reload();
                     }
                  });
               },
               error: function(xhr) {
                  let errorMessage = 'An error occurred while cloning the invoice.';
                  if (xhr.responseJSON && xhr.responseJSON.errors) {
                     errorMessage = xhr.responseJSON.errors.error || errorMessage;
                  }
                  Swal.fire({
                     title: 'Error!',
                     text: errorMessage,
                     icon: 'error',
                     confirmButtonText: 'OK'
                  });
               }
            });
         }
      });
   }
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
</script>
>>>>>>> Stashed changes
=======
</script>
>>>>>>> Stashed changes
=======
</script>
>>>>>>> Stashed changes
=======
</script>
>>>>>>> Stashed changes
