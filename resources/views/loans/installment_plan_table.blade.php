<table class="table table-striped">
    <thead class="text-center">
        <tr>
            <th>#</th>
            <th>Due Date</th>
            <th>Opening Balance</th>
            <th>Principal</th>
            <th>Interest</th>
            <th>EMI</th>
            <th>Status</th>
            <th>Paid Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $inst)
        <tr class="text-center">
            <td>{{ $inst->installment_no }}</td>
            <td>{{ $inst->due_date->format('d M Y') }}</td>
            <td>{{ number_format($inst->opening_balance, 2) }}</td>
            <td>{{ number_format($inst->principal_amount, 2) }}</td>
            <td>{{ number_format($inst->interest_amount, 2) }}</td>
            <td>{{ number_format($inst->total_amount, 2) }}</td>
            <td>{!! $inst->status_badge !!}</td>
            <td>{{ $inst->paid_date ? $inst->paid_date->format('d M Y') : '-' }}</td>
            <td>
                @include('loans.partials.pay_installment_button', ['installment' => $inst, 'loan' => $loan])
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
