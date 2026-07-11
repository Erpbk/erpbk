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
            <th>Payment From</th>
            <th>Payment To</th>
            <th>Remarks</th>
            <th>Created By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $voucher)
        @php
            $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
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
            <td>{{ $accounts[$voucher->payment_from] ?? 'N/A' }}</td>
            <td>{{ $accounts[$voucher->payment_to] ?? 'N/A' }}</td>
            <td class="text-start">{{ $voucher->remarks ?? '-' }}</td>
            <td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="10" class="text-center py-5">
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
