@php
    $actionInvoice = $actionInvoice ?? \App\Models\RiderInvoices::with('rider')->find($id);
    $actionStatus = (int) ($actionInvoice->status ?? 0);
    $actionLocked = in_array($actionStatus, [1, 3], true);
    $actionPaymentId = ($actionLocked && $actionInvoice)
        ? \App\Support\InvoicePaymentLink::latestId(
            $actionInvoice,
            $actionInvoice->rider?->account_id ? (int) $actionInvoice->rider->account_id : null
        )
        : null;
@endphp
{!! Form::open(['route' => ['riderInvoices.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('riders_invoices_view')
    <a href="{{ route('riderInvoices.show', $id) }}" class='btn btn-default btn-sm' target="_blank">
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    @if(! $actionLocked)
    @can('riders_invoices_edit')
    <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="{{ route('riderInvoices.edit', $id) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @elseif($actionPaymentId)
    @canany(['riders_payments_edit', 'cash_&_banks_payments_edit'])
    <a href="javascript:void(0);" data-title="Edit Payment" data-size="xl" data-action="{{ route('payments.edit', $actionPaymentId) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-money-bill"></i>
    </a>
    @endcanany
    @endif
    @can('riders_invoices_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => 'return confirm("Are you sure?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}