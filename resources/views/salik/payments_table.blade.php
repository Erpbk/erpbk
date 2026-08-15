@php
    $__companySlug = \App\Support\CompanyRouteContext::slug();
    $voucherRouteParams = static function ($voucherKey) use ($__companySlug): array {
        $params = ['voucher' => $voucherKey];
        if (!empty($__companySlug)) {
            $params['company_slug'] = $__companySlug;
        }
        return $params;
    };
    $listSidebarParams = !empty($__companySlug) ? ['company_slug' => $__companySlug] : [];
@endphp
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Voucher ID</th>
            <th>Payment Date</th>
            <th>Billing Month</th>
            <th>Salik Count</th>
            <th>Amount ({{ \App\Helpers\Currency::code() }})</th>
            <th>Reference</th>
            <th>Remarks</th>
            <th>Created By</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $voucher)
        @php
            $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
            $pendingDeletion = method_exists($voucher, 'isPendingDeletion') && $voucher->isPendingDeletion();
        @endphp
        <tr class="text-center">
            <td>
                <a href="javascript:void(0);" class="text-primary show-voucher-panel"
                   data-action="{{ route('vouchers.show', $voucherRouteParams($voucher->id)) }}"
                   data-title="Salik Voucher #{{ $voucherId }}"
                   data-collapse-sidebar="1"
                   data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">
                    {{ $voucherId }}
                </a>
            </td>
            <td>{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</td>
            <td>{{ $voucher->billing_month ? \App\Helpers\Common::MonthFormat($voucher->billing_month) : 'N/A' }}</td>
            <td><span class="badge bg-info">{{ $voucher->salik_count ?? 0 }}</span></td>
            <td class="num">{{ number_format($voucher->amount, 2) }}</td>
            <td class="text-start">{{ $voucher->reference_number ?: '-' }}</td>
            <td class="text-start">{{ $voucher->remarks ?? '-' }}</td>
            <td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>
            <td>
                @if($pendingDeletion)
                    @include('delete_requests._pending_badge', ['model' => $voucher])
                @else
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('rta_saliks_payment_edit')
                            <a href="{{ route('salik.payment.edit', $voucher->id) }}" class="dropdown-item waves-effect">
                                <i class="fa fa-edit my-1"></i> Edit
                            </a>
                            <a href="javascript:void(0);"
                               class="dropdown-item waves-effect unpay-salik-voucher"
                               data-url="{{ route('salik.payment.unpay', $voucher->id) }}"
                               data-label="{{ $voucherId }}"
                               data-count="{{ $voucher->salik_count ?? 0 }}"
                               data-amount="{{ \App\Helpers\Currency::format($voucher->amount, 2) }}">
                                <i class="fa fa-undo my-1"></i> Unpay
                            </a>
                            @endcan
                        </div>
                    </div>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center py-5">
                <h3>No payment records found</h3>
                <p class="text-muted">Try adjusting your filters or record a new salik payment.</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
