<table class="table table-striped">
    <thead class="text-center">
        <tr>
            <th>Loan #</th>
            <th>Bank</th>
            <th>Installment #</th>
            <th>Due Date</th>
            <th>EMI</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $inst)
        <tr class="text-center">
            <td><a href="{{ route('loans.show', $inst->loan_id) }}">{{ $inst->loan?->loan_number }}</a></td>
            <td>{{ $inst->loan?->bank_name }}</td>
            <td>{{ $inst->installment_no }}</td>
            <td>{{ $inst->due_date->format('d M Y') }}</td>
            <td>{{ number_format($inst->total_amount, 2) }}</td>
            <td>{!! $inst->status_badge !!}</td>
            <td>
                @include('loans.partials.pay_installment_button', ['installment' => $inst])
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No upcoming installments.</td></tr>
        @endforelse
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
