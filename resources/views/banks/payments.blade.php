@extends('banks.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-body table-responsive py-0" id="table-data">
                <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
                    <thead class="text-center">
                        <tr role="row">
                            <th title="Bank" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Bank: activate to sort column ascending">Payer Account</th>
                            <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Recipient Account</th>
                            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                            <th title="Date of Payment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Payment: activate to sort column ascending">Date of Payment</th>
                            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                            <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher No: activate to sort column ascending">Voucher No</th>
                            <th title="Voucher Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher Type: activate to sort column ascending">Voucher Type</th>
                            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $payment)
                        <tr>
                            <td>
                                @php
                                $bank = $payment->bank_id ? \App\Models\Banks::find($payment->bank_id) : null;
                                @endphp
                                {{ $bank ? $bank->name : '-' }}
                            </td>
                            <td>
                                @php
                                $account = $payment->account_id ? \App\Models\Accounts::find($payment->account_id) : null;
                                @endphp
                                {{ $account ? $account->name : '-' }}
                            </td>
                            <td>AED {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->date_of_payment }}</td>
                            <td>{{ $payment->billing_month }}</td>
                            <td>
                                @php
                                $voucher = \App\Models\Vouchers::where('ref_id', $payment->id)->where('voucher_type', 'JV')->first();
                                @endphp
                                {{ $voucher ? $voucher->trans_code : '-' }}
                            </td>
                            <td>{{ $voucher ? $voucher->voucher_type : '-' }}</td>
                            <td>
                                <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-pencil"></i></a>
                            </td>
                            <td></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($data->isEmpty())
                    <div class="text-center mt-5">
                        <h3>No Payments found</h3> 
                    </div>
                @endif
                @if(method_exists($data, 'links'))
                    {!! $data->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
    </div>
@endsection