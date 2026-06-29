<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Loan #</th>
            <th>Bank</th>
            <th>Principal</th>
            <th>Outstanding</th>
            <th>Rate %</th>
            <th>Interest</th>
            <th>EMI</th>
            <th>Maturity</th>
            <th>Status</th>
            <th width="120">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $loan)
        <tr class="text-center">
            <td><a href="{{ route('loans.show', $loan->id) }}">{{ $loan->loan_number }}</a></td>
            <td>{{ $loan->bank?->name ?? '-' }}</td>
            <td>{{ number_format($loan->principal_amount, 2) }}</td>
            <td>{{ number_format($loan->outstanding_principal, 2) }}</td>
            <td>{{ number_format($loan->interest_rate, 2) }}</td>
            <td>{{ $loan->interest_calculation_method_label }}</td>
            <td>{{ $loan->emi_amount ? number_format($loan->emi_amount, 2) : '-' }}</td>
            <td>{{ $loan->maturity_date ? $loan->maturity_date->format('d M Y') : '-' }}</td>
            <td>{!! $loan->status_badge !!}</td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill border-0 p-2" data-bs-toggle="dropdown">
                        <i class="ti ti-dots"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @can('loan_view')
                        <a href="{{ route('loans.show', $loan->id) }}" class="dropdown-item"><i class="fa fa-eye"></i> View</a>
                        @endcan
                        @can('loan_edit')
                        @if($loan->isEditable())
                        <a href="javascript:void(0);" class="dropdown-item show-modal" data-size="lg" data-title="Edit Loan" data-action="{{ route('loans.edit', $loan->id) }}"><i class="fa fa-edit"></i> Edit</a>
                        @endif
                        @endcan
                        @can('loan_disburse')
                        @if($loan->status === 'draft')
                        <a href="javascript:void(0);" class="dropdown-item" onclick="confirmDisburse('{{ route('loans.disburse', $loan->id) }}')">
                            <i class="fa fa-check-circle text-success"></i> Disburse Loan
                        </a>
                        @endif
                        @endcan
                        @can('loan_delete')
                        @if($loan->status !== 'active')
                        <a href="javascript:void(0);" class="dropdown-item" onclick="confirmDelete('{{ route('loans.destroy', $loan->id) }}')"><i class="fa fa-trash"></i> Delete</a>
                        @endif
                        @endcan
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center text-muted">No loans found.</td></tr>
        @endforelse
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
