
<table class="table  dataTable" id="dataTableBuilder">
    @php $vf = static fn (string $f): bool => field_visible('cheques', $f); @endphp
    <thead class="text-center">
        <tr role="row">
            @if($vf('reference'))<th title="Reference" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Reference: activate to sort column ascending">Reference</th>@endif
            @if($vf('cheque_number'))<th title="Cheque No." class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Cheque No.: activate to sort column ascending">Cheque No.</th>@endif
            @if($vf('cheque_date'))<th title="Cheque date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Cheque date: activate to sort column ascending">Cheque date</th>@endif
            @if($vf('type'))<th title="Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Type: activate to sort column ascending">Type</th>@endif
            <th title="Sender/Receiver" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Sender/Receiver: activate to sort column ascending">Sender/Reciever</th>
            @if($vf('amount'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>@endif
            @if($vf('voucher_id'))<th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>@endif
            @if($vf('attachment'))<th title="Attachmnet" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending">Attachment</th>@endif
            @if($vf('description'))<th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>@endif
            @if($vf('status'))<th title="Voucher Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>@endif
            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
          $__companySlug = \App\Support\CompanyRouteContext::slug();
          $voucherShowParams = static function ($voucherId) use ($__companySlug): array {
            $params = ['voucher' => $voucherId];
            if (!empty($__companySlug)) {
              $params['company_slug'] = $__companySlug;
            }
            return $params;
          };
        @endphp
        @foreach($data as $cheque)
        <tr data-id="{{ $cheque->id }}">
            @if($vf('reference'))<td>{{ $cheque->reference ?? '-' }}</td>@endif
            @if($vf('cheque_number'))<td>
                <a href="javascript:void(0);" class="show-modal text-primary" data-size="xl"
                data-action="{{ route('cheques.show', $cheque->id) }}"
                data-title="Cheque Details">
                    {{ $cheque->cheque_number }}
                </a>
            </td>@endif
            @if($vf('cheque_date'))<td>{{ $cheque->cheque_date?->format('d M Y') ?? '-' }}</td>@endif
            @if($vf('type'))<td>
                @if($cheque->type == 'payable')
                    <span class="badge border border-danger text-black">Payable</span>
                @else
                    <span class="badge border border-success text-black">Receivable</span>
                @endif
                @if($cheque->is_security)
                    <small class=" badge border border-danger text-warning mt-2" style="white-space: nowrap">Security Cheque</small>
                @endif
            </td>@endif
            <td>
                @if($cheque->type == 'payable')

                    <a href="{{ route('accounts.ledger', ['company_slug' => \App\Support\CompanyRouteContext::slug()]) }}?account={{ $cheque->payee_account }}" target="_blank">
                        {{ $cheque->payee_name ?? '-' }}
                    </a>
                @else
                    @if($cheque->payer?->ref_name == 'Rider')
                        <a href="{{ route('rider.ledger',  $cheque->payer->ref_id)}}" target="_blank">
                            {{ $cheque->payer_name ?? '-' }}
                        </a>
                    @else
                        <a href="{{ route('accounts.ledger', ['company_slug' => \App\Support\CompanyRouteContext::slug()]) }}?account={{ $cheque->payer_account }}" target="_blank">
                            {{ $cheque->payer_name ?? '-' }}
                        </a>
                    @endif
                @endif
            </td>
            @if($vf('amount'))<td>{{ \App\Helpers\Currency::format($cheque->amount, 2) }}</td>@endif
            @if($vf('voucher_id'))<td>
                @if($cheque->voucher_id)
                    <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $voucherShowParams($cheque->voucher_id)) }}" class="text-primary show-voucher-panel" data-title="Voucher Against Cheque#{{ $cheque->cheque_number }}" data-collapse-sidebar="1">
                        {{ $cheque->voucher->voucher_type . '-'. $cheque->voucher_id }}
                    </a>
                @else
                    -
                @endif
            </td>@endif
            @if($vf('attachment'))<td>
                @if($cheque->attachment)
                    <a href="{{ storage_url('vouchers/' . $cheque->attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fa fa-file"></i> View
                    </a>
                @else
                    -
                @endif
            </td>@endif
            @if($vf('description'))<td class="text-start">{{ $cheque->description }}</td>@endif
            @php
                $badge=[
                    'Issued' => 'primary',
                    'Cleared' => 'success',
                    'Returned' => 'danger',
                    'Stop Payment' => 'warning',
                    'Lost' => 'secondary',
                ];
            @endphp
            @if($vf('status'))<td>
                <span class="badge bg-{{ $badge[$cheque->status] ?? 'secondary' }}">{{ $cheque->status }}</span>
            </td>@endif
            <td style="position: relative;">
                <div class="dropdown">
                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $cheque->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $cheque->id }}" style="z-index: 1050;">
                    @include('layouts.partials.module_contract_action', [
                        'module' => 'cheques',
                        'recordId' => $cheque->id,
                    ])
                    @can('cash_&_banks_cheques_edit')
                        @if($cheque->status != 'Cleared')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Edit {{ ucwords($cheque->type) }} Cheque" data-action="{{ route('cheques.edit', $cheque->id) }}">
                            <i class="fa fa-edit my-1"></i> Edit
                        </a>
                        @endif
                        @if($cheque->status == 'Issued')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Change Status" data-action="{{ route('cheques.status-form', $cheque->id) }}">
                            <i class="fa fa-exchange-alt my-1"></i> Change Status
                        </a>
                        @endif
                    @endcan
                    @can('cash_&_banks_cheques_delete')
                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-cheque' 
                        data-url="{{ route('cheques.destroy', $cheque->id) }}">
                        <i class="fa fa-trash my-1"></i> Delete
                    </a>
                    @endcan
                </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($data->isEmpty())
    <div class="text-center mt-5">
        <h3>No Cheques found</h3> 
    </div>
@endif
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    });
    $('#quickSearch').on('keyup change', function() {
        $('#dataTableBuilder').DataTable().search(this.value).draw();
    });
    $(document).ready(function(){
        $(document).on('click', '.delete-cheque', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Cheque has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete Cheque. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                                'error'
                            );
                        }
                    });
                }
            });
        });
    })
</script>
@endsection
@include('delete_requests._pending_table_script', ['items' => $data])
