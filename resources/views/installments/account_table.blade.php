<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th>Rider ID</th>
            <th style="width: 220px;">Account Name</th>
            <th>Person Code</th>
            <th>Pending Installments</th>
            <th>Pending Amount</th>
            <th>Paid Installments</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
        @php
        $riderKeys = array_values(array_unique(array_filter([
            (int) $r->rider_id,
            (int) $r->id,
            $r->account_id ? (int) $r->account_id : null,
        ])));
        $pendingCount = company_table('visa_installment_plans')->whereIn('rider_id', $riderKeys)->where('status', 'pending')->count();
        $pendingAmount = company_table('visa_installment_plans')->whereIn('rider_id', $riderKeys)->where('status', 'pending')->sum('amount');
        $paidCount = company_table('visa_installment_plans')->whereIn('rider_id', $riderKeys)->where('status', 'paid')->count();
        @endphp
        <tr class="text-center">
            <td>{{ $r->rider->rider_id ?? '-' }}</td>
            <td class="text-start">
                <a href="{{ route('Installments.installmentPlan', $r->id) }}">{{ $r->name }}</a>
            </td>
            <td>{{ $r->rider->person_code ?? '-' }}</td>
            <td>{{ $pendingCount }}</td>
            <td>{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $pendingAmount, 2) }}</td>
            <td>{{ $paidCount }}</td>
            <td>
                <a href="{{ route('Installments.installmentPlan', $r->id) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
{!! $data->links('pagination') !!}
