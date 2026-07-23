@php $vf = static fn (string $f): bool => field_visible('loan', $f); @endphp
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            @if($vf('loan_number'))<th>Loan #</th>@endif
            @if($vf('bank_id'))<th>Bank</th>@endif
            @if($vf('principal_amount'))<th>Principal</th>@endif
            @if($vf('outstanding_principal'))<th>Outstanding</th>@endif
            @if($vf('interest_rate'))<th>Rate %</th>@endif
            @if($vf('interest_calculation_method'))<th>Interest</th>@endif
            @if($vf('emi_amount'))<th>EMI</th>@endif
            @if($vf('maturity_date'))<th>Maturity</th>@endif
            @if($vf('status'))<th>Status</th>@endif
            <th width="120">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $loan)
        <tr class="text-center" data-id="{{ $loan->id }}">
            @if($vf('loan_number'))<td><a href="{{ route('loans.show', $loan->id) }}">{{ $loan->loan_number }}</a></td>@endif
            @if($vf('bank_id'))<td>{{ $loan->bank?->name ?? '-' }}</td>@endif
            @if($vf('principal_amount'))<td>{{ number_format($loan->principal_amount, 2) }}</td>@endif
            @if($vf('outstanding_principal'))<td>{{ number_format($loan->outstanding_principal, 2) }}</td>@endif
            @if($vf('interest_rate'))<td>{{ number_format($loan->interest_rate, 2) }}</td>@endif
            @if($vf('interest_calculation_method'))<td>{{ $loan->interest_calculation_method_label }}</td>@endif
            @if($vf('emi_amount'))<td>{{ $loan->emi_amount ? number_format($loan->emi_amount, 2) : '-' }}</td>@endif
            @if($vf('maturity_date'))<td>{{ $loan->maturity_date ? $loan->maturity_date->format('d M Y') : '-' }}</td>@endif
            @if($vf('status'))<td>{!! $loan->status_badge !!}</td>@endif
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill border-0 p-2" data-bs-toggle="dropdown">
                        <i class="ti ti-dots"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @can('loans_view')
                        <a href="{{ route('loans.show', $loan->id) }}" class="dropdown-item"><i class="fa fa-eye"></i> View</a>
                        @endcan
                        @can('loans_edit')
                        @if($loan->isEditable())
                        <a href="javascript:void(0);" class="dropdown-item show-modal" data-size="lg" data-title="Edit Loan" data-action="{{ route('loans.edit', $loan->id) }}"><i class="fa fa-edit"></i> Edit</a>
                        @endif
                        @endcan
                        @canany(['loans_create', 'loans_edit'])
                        @if($loan->status === 'draft')
                        <a href="javascript:void(0);" class="dropdown-item" onclick="confirmDisburse('{{ route('loans.disburse', $loan->id) }}')">
                            <i class="fa fa-check-circle text-success"></i> Disburse Loan
                        </a>
                        @endif
                        @endcanany
                        @can('loans_delete')
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
@include('delete_requests._pending_table_script', ['items' => $data])
