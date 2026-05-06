
<div class="card-body table-responsive py-0" id="table-data">
   <table class="table dataTable no-footer" id="dataTableBuilder">
      <thead class="text-center">
         <tr role="row">
            <th title="Invoice Number" class="sorting" rowspan="1" colspan="1">Invoice #</th>
            <th title="Inv Date" class="sorting" rowspan="1" colspan="1">Inv Date</th>
            <th title="Billing Month" class="sorting" rowspan="1" colspan="1">Billing Month</th>
            <th title="Leasing Company" class="sorting" rowspan="1" colspan="1">Customer</th>
            <th title="Subtotal" class="sorting" rowspan="1" colspan="1">Subtotal</th>
            <th title="Vat" class="sorting" rowspan="1" colspan="1">Vat</th>
            <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Total Amount</th>
            <th title="Attachments" class="sorting" rowspan="1" colspan="1">Attachments</th>
            <th title="Status" class="sorting" rowspan="1" colspan="1">Status</th>
            <th title="Action" width="150px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
         </tr>
      </thead>
      <tbody>
         @foreach($invoices as $invoice)
         <tr class="text-center">
            <td>
               <a href="{{ route('customer_invoices.show', $invoice) }}" target="_blank">
                  {{ $invoice->invoice_number ?? '-' }}
               </a>
         </td>
            <td>{{ \Carbon\Carbon::parse($invoice->inv_date)->format('d M Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
            <td>{{ $invoice->Customer->name ?? '-' }}</td>
            <td>{{ \App\Helpers\Currency::format($invoice->subtotal ?? 0) }}</td>
            <td>{{ \App\Helpers\Currency::format($invoice->vat ?? 0) }}</td>
            <td><strong>{{ \App\Helpers\Currency::format($invoice->total ?? 0) }}</strong></td>
            <td>
               @if($invoice->attachment)
               <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank">
                  <i class="fa fa-file-pdf-o"></i> View Attachment
               </a>
               @else
                  -
               @endif
            </td>
            <td>
               @if($invoice->status == 'paid')
                  <span class="badge bg-success">Paid</span>
               @elseif($invoice->status == 'partially_paid')
                  <span class="badge bg-warning">Partially Paid</span>
                  <small>({{ \App\Helpers\Currency::format(($invoice->total - array_sum($invoice->partial_paid_amount ?? []))) }} due)</small>
               @else
                  <span class="badge bg-secondary">Unpaid</span>
               @endif
            </td>
            <td>
               <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown">
                     @can('customer_invoice_create')
                     <a href="javascript:void(0);" data-action="{{ route('customer_invoice.edit', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Invoice">
                        <i class="fa fa-edit mx-1"></i> Edit
                     </a>
                     @endcan
                     @can('customer_invoice_create')
                     <a href="javascript:void(0);" data-action="{{ route('customer_invoice.clone', $invoice) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Clone Invoice">
                        <i class="fa fa-copy mx-1 text-primary"></i> Clone
                     </a>
                     @endcan
                     @can('customer_invoice_delete')
                     {!! Form::open(['route' => ['customer_invoices.destroy', $invoice], 'method' => 'DELETE', 'style' => 'display:inline;', 'id'=>'formajax']) !!}
                     {!! Form::button('<i class="fa fa-trash mx-1"></i> Delete', [
                     'type' => 'submit',
                     'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                     'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
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
</div>
@if(method_exists($invoices, 'links'))
{!! $invoices->links('components.global-pagination') !!}
@endif
@section('page-script')
<script>
    $('#dataTableBuilder').DataTable({
        "paging": true, // Enable DataTables pagination
        "pageLength": 50, // Items per page
        "searching": true, // Enable search
        "ordering": false, // Enable column sorting
        "info": true, // Show "Showing X of Y entries"
        "autoWidth": true, // Better column width handling
        "dom": "<'row'<'col-md-12'tr>>" +
            "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
        "language": {
            "emptyTable": "No invoices found."
        }
    });
    $('#quickSearch').on('keyup change', function() {
        $('#dataTableBuilder').DataTable().search(this.value).draw();
    });
</script>
@endsection
