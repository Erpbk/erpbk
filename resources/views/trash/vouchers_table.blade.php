<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date (Deleted)</th>
            <th title="Voucher ID" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
            <th title="Trans Code" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Trans Code: activate to sort column ascending">Trans Code</th>
            <th title="Billing Month" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
            <th title="Type" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Type: activate to sort column ascending">Type</th>
            <th title="Amount" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
            <th title="Created By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Deleted By</th>
            <th title="File" class="sorting_disabled" rowspan="1" colspan="1" aria-label="File">File</th>
            <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Actions">Actions</th>
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
            <td>{{ \App\Helpers\Common::DateFormat($voucher->deleted_at) }}</td>
            <td>
                <a href="javascript:void(0);" data-action="{{ route('trash.show', ['vouchers', $voucher->id]) }}" class="text-primary show-modal" data-title="Voucher Details - {{ $voucherId }} (Deleted)" data-size="xl">{{ $voucherId }}</a>
            </td>
            <td>{{ $voucher->trans_code }}</td>
            <td>{{ strtoupper(date('M-y', strtotime($voucher->billing_month))) }}</td>
            <td>
                @php
                $voucherTypes = \App\Helpers\General::VoucherType();
                @endphp
                <span class="badge bg-primary">{{ $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type }}</span>
            </td>
            <td class="text-end">{{ number_format($voucher->amount, 2) }}</td>
            <td>{{ \App\Helpers\Common::UserName($voucher->deleted_by) }}</td>
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

@if(isset($totalPages))
<div class="pagination-wrapper card mt-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- Left side: Records info and Show entries dropdown -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted">
                    Showing {{ count($trashedRecords) }} of {{ $totalCount }} entries
                </span>
                <div class="d-flex align-items-center gap-2">
                    <label for="perPageSelect" class="form-label mb-0">Show:</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 || ($perPage != 'all' && $perPage != 10 && $perPage != 50 && $perPage != 100) ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        <option value="all" {{ $perPage == 'all' ? 'selected' : '' }}>All ({{ $totalCount }})</option>
                    </select>
                </div>
            </div>

            <!-- Right side: Pagination -->
            @if($totalPages > 1)
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('trash.index', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}">
                            Previous
                        </a>
                    </li>

                    @for($i = 1; $i <= $totalPages; $i++)
                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('trash.index', array_merge(request()->except('page'), ['page' => $i])) }}">
                            {{ $i }}
                        </a>
                        </li>
                        @endfor

                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('trash.index', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}">
                                Next
                            </a>
                        </li>
                </ul>
            </nav>
            @endif
        </div>
    </div>
</div>
@endif

<script>
    // Initialize Bootstrap dropdowns when this content is loaded
    (function() {
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

        // Use DOMContentLoaded or run immediately if DOM is already loaded
        function init() {
            setTimeout(tryInitialize, 100);

            // Handle "Show entries" dropdown change
            const perPageSelect = document.getElementById('perPageSelect');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    const perPage = this.value;
                    const url = new URL(window.location.href);

                    // Update or add per_page parameter
                    url.searchParams.set('per_page', perPage);

                    // Reset to page 1 when changing per_page
                    url.searchParams.set('page', '1');

                    // Redirect to new URL
                    window.location.href = url.toString();
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>