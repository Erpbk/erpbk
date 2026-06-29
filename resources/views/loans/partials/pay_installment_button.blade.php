@can('loan_repay')
@php
if (isset($loan) && $loan && ! $installment->relationLoaded('loan')) {
    $installment->setRelation('loan', $loan);
}
@endphp
@if($installment->canBePaid())
<a href="javascript:void(0);"
   class="btn btn-sm btn-primary show-modal"
   data-size="xl"
   data-title="Pay Installment #{{ $installment->installment_no }} — {{ $installment->loan?->loan_number }}"
   data-action="{{ route('loanInstallments.payForm', $installment->id) }}">
    Pay
</a>
@endif
@endcan
