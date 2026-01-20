<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Voucher ID" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
            <th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Trans Code" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Trans Code: activate to sort column ascending">Trans Code</th>
            <th title="Billing Month" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
            <th title="Type" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Type: activate to sort column ascending">Type</th>
            <th title="Amount" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
            <th title="Created By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Created By</th>
            <th title="Updated By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Updated By: activate to sort column ascending">Updated By</th>
            <th title="File" class="sorting_disabled" rowspan="1" colspan="1" aria-label="File">File</th>
            <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Actions">Actions</th>
            <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
                <a class="openFilterSidebar" href="javascript:void(0);" title="Filters"> <i class="fa fa-search"></i></a>
            </th>
            <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
                <a class="openColumnControlSidebar" href="javascript:void(0);" title="Column Control"> <i class="fa fa-columns"></i></a>
            </th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data) && $data->count() > 0)
        @foreach($data as $voucher)
        <tr class="text-center">
            <td>
                @php
                $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
                @endphp
                <a href="{{ route('vouchers.show', $voucher->id) }}" class="text-primary" target="_blank">{{ $voucherId }}</a>
            </td>
            <td>{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</td>
            <td>{{ $voucher->trans_code }}</td>
            <td>{{ \App\Helpers\Common::MonthFormat($voucher->billing_month) }}</td>
            <td>
                @php
                $voucherTypes = \App\Helpers\General::VoucherType();
                @endphp
                <span class="badge bg-primary">{{ $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type }}</span>
            </td>
            <td class="text-end">{{ number_format($voucher->amount, 2) }}</td>
            <td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>
            <td>{{ \App\Helpers\Common::UserName($voucher->Updated_By) }}</td>
            <td>
                @if($voucher->attach_file)
                @if($voucher->voucher_type == 'RFV')
                <a href="{{ url('storage/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fa fa-file"></i> View
                </a>
                @elseif($voucher->voucher_type == 'LV')
                <a href="{{ url('storage/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fa fa-file"></i> View
                </a>
                @else
                <a href="{{ url('storage/vouchers/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fa fa-file"></i> View
                </a>
                @endif
                @else
                <span class="text-muted">-</span>
                @endif
            </td>
            <td style="position: relative;">
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $voucher->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $voucher->id }}" style="z-index: 1050;">
                        @can('voucher_document')
                        <li><a href="javascript:void(0);" data-size="sm" data-title="Upload Document"
                                data-action="{{ url('voucher/attach_file/'.$voucher->id) }}" class='dropdown-item waves-effect show-modal'>
                                <i class="fa fa-file my-1"></i> Upload Document
                            </a></li>
                        @endcan
                        @can('voucher_view')
                        <li><a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank" class='dropdown-item waves-effect'>
                                <i class="fa fa-eye my-1"></i> View
                            </a></li>
                        @endcan
                        @can('voucher_edit')
                        @if(in_array($voucher->voucher_type, ['AL', 'COD', 'PN', 'INC', 'PAY', 'VC', 'JV']))
                        <li><a href="javascript:void(0);" data-size="xl"
                                data-title="Edit Voucher No. {{$voucher->voucher_type.'-'.str_pad($voucher->id,4,'0',STR_PAD_LEFT)}}"
                                data-action="{{ route('vouchers.edit', $voucher->trans_code) }}"
                                class='dropdown-item waves-effect show-modal'>
                                <i class="fa fa-edit my-1"></i> Edit
                            </a></li>
                        @endif
                        @endcan
                        @can('voucher_delete')
                        @if(in_array($voucher->voucher_type, ['AL', 'COD', 'PN', 'INC', 'PAY', 'VC', 'JV', 'LV']))
                        <li><a href="javascript:void(0);" onclick="deleteVoucher('{{ $voucher->trans_code }}')" class='dropdown-item waves-effect text-danger'>
                                <i class="fa fa-trash my-1"></i> Delete
                            </a></li>
                        @endif
                        @endcan
                        </ul>
                    </div>
            </td>
            <td></td>
            <td></td>
        </tr>
        @endforeach
        @else
        <tr>
            <td colspan="12" class="text-center">
                <div class="py-4">
                    <i class="fa fa-info-circle text-muted"></i>
                    <p class="text-muted mb-0">No vouchers found</p>
                </div>
            </td>
        </tr>
        @endif
    </tbody>
</table>

@if(isset($data))
<div class="pagination-wrapper">
    {!! $data->appends(request()->query())->links('pagination') !!}
</div>
@endif

<script>
    function deleteVoucher(transCode) {
        if (confirm('Are you sure you want to delete this voucher?')) {
            $.ajax({
                url: '/vouchers/' + transCode,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(result) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Voucher deleted successfully');
                    } else {
                        alert('Voucher deleted successfully');
                    }
                    location.reload();
                },
                error: function(xhr) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error deleting voucher');
                    } else {
                        alert('Error deleting voucher');
                    }
                }
            });
        }
    }

    // Initialize Bootstrap dropdowns when this content is loaded
    $(document).ready(function() {
        console.log('Table content loaded, initializing dropdowns');

        // Wait for Bootstrap to be available
        var attempts = 0;
        var maxAttempts = 10;

        function tryInitialize() {
            attempts++;

            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                // Initialize Bootstrap 5 dropdowns for this content
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                    try {
                        return new bootstrap.Dropdown(dropdownToggleEl);
                    } catch (e) {
                        console.warn('Failed to initialize dropdown in table:', e);
                        return null;
                    }
                }).filter(Boolean);

                console.log('Dropdowns initialized in table:', dropdownList.length);
            } else if (attempts < maxAttempts) {
                console.log('Bootstrap not ready in table, retrying...', attempts);
                setTimeout(tryInitialize, 100);
            } else {
                console.warn('Bootstrap dropdown initialization failed in table after', maxAttempts, 'attempts');
            }
        }

        setTimeout(tryInitialize, 100);
    });
</script>