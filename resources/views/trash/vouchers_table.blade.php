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
        @if(isset($trashedRecords) && count($trashedRecords) > 0)
        @foreach($trashedRecords as $item)
        @php
            $voucher = $item['record'];
            $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
        @endphp
        <tr class="text-center">
            <td>
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
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $item['id'] }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $item['id'] }}" style="z-index: 1050;">
                        @if($item['can_restore'])
                        <a href="javascript:void(0);" class="dropdown-item waves-effect restore-item" data-form-id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}">
                            <i class="fa fa-undo text-success my-1"></i> Restore
                        </a>
                        <form id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route('trash.restore', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                        @endif

                        @if($item['can_force_delete'])
                        <a href="javascript:void(0);" class="dropdown-item waves-effect delete-item" data-form-id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}">
                            <i class="fa fa-trash-o text-danger my-1"></i> Delete Forever
                        </a>
                        <form id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route('trash.force-destroy', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endif
                    </div>
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
                    <p class="text-muted mb-0">No deleted vouchers found</p>
                </div>
            </td>
        </tr>
        @endif
    </tbody>
</table>

@if(isset($totalPages) && $totalPages > 1)
<div class="pagination-wrapper">
    <nav>
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage - 1])) }}">
                    Previous
                </a>
            </li>

            @for($i = 1; $i <= $totalPages; $i++)
                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $i])) }}">
                    {{ $i }}
                </a>
                </li>
                @endfor

                <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage + 1])) }}">
                        Next
                    </a>
                </li>
        </ul>
    </nav>
    <p class="text-center text-muted mt-2 mb-0">
        Showing {{ count($trashedRecords) }} of {{ $totalCount }} deleted records
    </p>
</div>
@endif

<script>
    // Initialize Bootstrap dropdowns when this content is loaded
    $(document).ready(function() {
        console.log('Voucher trash table content loaded, initializing dropdowns');

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

                console.log('Dropdowns initialized in voucher trash table:', dropdownList.length);
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

