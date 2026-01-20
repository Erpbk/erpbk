<div class="container-fluid p-4">
    @php
    $voucher_type = App\Helpers\General::VoucherType($voucher->voucher_type);
    $i=0;
    @endphp

    <style>
        .voucher-modal-table {
            width: 100%;
            font-family: sans-serif;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .voucher-modal-table th,
        .voucher-modal-table td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }

        .voucher-modal-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .voucher-info-table {
            width: 100%;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .deleted-badge {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
    </style>

    @isset($voucher)
    <div class="mb-4">
        <h4 class="mb-3">
            {{$voucher_type}} # {{ $voucher->voucher_type . '-' . str_pad($voucher->id, '4', '0', STR_PAD_LEFT) }}
            <span class="deleted-badge">
                <i class="fa fa-trash"></i> Deleted
            </span>
        </h4>

        <table class="voucher-info-table">
            <tr>
                <td style="width: 30%;"><strong>Voucher No:</strong></td>
                <td>{{ $voucher->voucher_type . '-' . str_pad($voucher->id, '4', '0', STR_PAD_LEFT) }}</td>
                <td style="width: 20%;"><strong>Voucher Date:</strong></td>
                <td>{{ $voucher->trans_date }}</td>
            </tr>
            <tr>
                <td><strong>Voucher Type:</strong></td>
                <td>{{$voucher_type}}</td>
                @isset($voucher->billing_month)
                <td><strong>Billing Month:</strong></td>
                <td>{{date('M-Y',strtotime($voucher->billing_month))}}</td>
                @else
                <td></td>
                <td></td>
                @endisset
            </tr>
            <tr>
                <td><strong>Created By:</strong></td>
                <td>{{ Auth::user()->where('id', $voucher->Created_By)->first()->name ?? 'N/A' }}</td>
                <td><strong>Creation Date:</strong></td>
                <td>{{ Illuminate\Support\Carbon::parse($voucher->created_at)->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td><strong>Deleted At:</strong></td>
                <td>{{ $voucher->deleted_at ? Illuminate\Support\Carbon::parse($voucher->deleted_at)->format('d-M-Y h:i A') : 'N/A' }}</td>
                <td><strong>Deleted By:</strong></td>
                <td>
                    @if(isset($voucher->deleted_by) && $voucher->deleted_by)
                        {{ Auth::user()->where('id', $voucher->deleted_by)->first()->name ?? 'System' }}
                    @else
                        System
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered voucher-modal-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Sr</th>
                    <th>Account Name</th>
                    <th>Particulars</th>
                    <th style="text-align: center; width: 120px;">Debit</th>
                    <th style="text-align: center; width: 120px;">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalD = 0;
                $totalC = 0;
                $fin_detail = DB::Table('rta_fines')->where('id' , $voucher->ref_id)->first();
                @endphp
                @if($voucher->transactions && $voucher->transactions->count() > 0)
                @foreach($voucher->transactions as $item)
                <tr>
                    <td style="text-align:center;">{{ $i+=1 }}</td>
                    <td>
                        @if($item->account)
                        {{ $item->account->account_code ?? '' }}-{{ $item->account->name ?? 'N/A' }}
                        @else
                        N/A
                        @endif
                    </td>
                    @if($voucher->voucher_type == 'RFV')
                    <td style="text-align: left;">
                        {{ $item->narration ?? '-' }}
                        @if($fin_detail)
                        <br><small class="text-muted">
                            <strong>Ticket No:</strong> {{$fin_detail->ticket_no ?? ''}},
                            <strong>Bike No:</strong> {{ $fin_detail->plate_no ?? '' }},
                            @if ($fin_detail->trip_date)
                            {{ \Carbon\Carbon::parse($fin_detail->trip_date)->format('d M Y') }}
                            @else
                            N/A
                            @endif
                        </small>
                        @endif
                    </td>
                    @else
                    <td style="text-align: left;">{{ $item->narration ?? '-' }}</td>
                    @endif
                    <td style="text-align: center;">{{ $item->debit ? number_format($item->debit, 2) : '-' }}</td>
                    <td style="text-align: center;">{{ $item->credit ? number_format($item->credit, 2) : '-' }}</td>
                </tr>
                @php
                $totalD+=$item->debit ?? 0;
                $totalC+=$item->credit ?? 0;
                @endphp
                @endforeach
                @else
                <tr>
                    <td colspan="5" class="text-center text-muted">No transactions found</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa;">
                    <td colspan="2" style="text-align: right;"><strong>Sub Total:</strong></td>
                    <td></td>
                    <td style="text-align: center;"><strong>{{ \App\Helpers\Account::show_bal_format($totalD) }}</strong></td>
                    <td style="text-align: center;"><strong>{{ \App\Helpers\Account::show_bal_format($totalC) }}</strong></td>
                </tr>
                <tr style="background-color: #e9ecef;">
                    <td colspan="2" style="text-align: right;"><strong>Total:</strong></td>
                    <td></td>
                    <td style="text-align: center;"><strong>AED {{ \App\Helpers\Account::show_bal_format($totalD) }}</strong></td>
                    <td style="text-align: center;"><strong>AED {{ \App\Helpers\Account::show_bal_format($totalC) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-3 text-end">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
    @else
    <div class="alert alert-danger">No Voucher found</div>
    @endisset
</div>

