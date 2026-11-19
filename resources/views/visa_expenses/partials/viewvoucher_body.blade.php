@php
$isEmployee = !empty($accounts->employee_id);
$rider = $isEmployee
    ? company_table('employees')->where('id', $accounts->employee_id)->first()
    : company_table('riders')->where('id', $accounts->rider_id)->first();
$personCode = $isEmployee ? ($rider->employee_id ?? '-') : ($rider->rider_id ?? '-');
$riderVisaBalance = company_table('visa_expenses')
->where('expense_account_id', $accounts->id)
->sum('amount');
$riderPaidTotal = company_table('visa_expenses')
->where('expense_account_id', $accounts->id)
->where('payment_status', 'paid')
->sum('amount');
$riderUnpaidTotal = company_table('visa_expenses')
->where('expense_account_id', $accounts->id)
->where('payment_status', 'unpaid')
->sum('amount');
$hideActions = !empty($deleteRequestPreview);
@endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Visa Staus #{{ $data->visa_status }}</h3>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @if(empty($deleteRequestPreview))
    @include('flash::message')
    @endif
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <div class="user-info text-center">
                                <h6>{{ $personCode . ' - ' . ($rider->name ?? '') }}</h6>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4"></h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Status:</b>
                                <span>
                                    @if(($rider->status ?? null) == '1')
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Rider ID:</b>
                                <span>{{ $personCode }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Person Code:</b>
                                <span>{{ $rider->person_code ?? '-' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Labour Card:</b>
                                <span>{{ $rider->labor_card_number ?? '-' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Policy #:</b>
                                <span>{{ $rider->policy_no ?? '-' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Visa Balance:</b>
                                <span>{{ \App\Helpers\Currency::format($riderVisaBalance, 2) }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Paid Total:</b>
                                <span class="text-success">{{ \App\Helpers\Currency::format($riderPaidTotal, 2) }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Unpaid Total:</b>
                                <span class="text-danger">{{ \App\Helpers\Currency::format($riderUnpaidTotal, 2) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">
                    <li class="nav-item"><a class="nav-link  active  " href="javascript:void(0)"><i class="ti ti-file-upload ti-sm me-1_5"></i>Files</a></li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <tr>
                                    <th>Visa Status</th>
                                    <td class="text-end">{{ $data->visa_status }}</td>
                                </tr>
                                <tr>
                                    <th>Rider name</th>
                                    @php
                                    $riderNameRow = company_table('riders')->where('id', $accounts->rider_id)->first();
                                    @endphp
                                    <td class="text-end">{{ $riderNameRow->name ?? ($rider->name ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td class="text-end">{{ $data->date }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td class="text-end">{{ \App\Helpers\Currency::format($data->amount , 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Attached File</th>
                                    <td class="text-end">
                                        @php
                                            $data->loadMissing('vouchers');
                                            $attachedVouchers = $data->vouchers->filter(fn ($v) => filled($v->attach_file));
                                        @endphp
                                        @forelse($attachedVouchers as $attachedVoucher)
                                            @php
                                                $attachPath = ltrim((string) $attachedVoucher->attach_file, '/');
                                                $attachUrl = str_contains($attachPath, '/')
                                                    ? url('storage/' . $attachPath)
                                                    : url('storage/vouchers/' . $attachPath);
                                                $attachName = basename($attachPath);
                                            @endphp
                                            <a href="{{ $attachUrl }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fa fa-file me-1"></i>{{ $attachName }}
                                            </a>
                                        @empty
                                            <span class="text-muted">No file attached</span>
                                            @if(!$hideActions)
                                            @can('visa_expense_entries_edit')
                                            @if($data->vouchers->isNotEmpty())
                                            <a href="javascript:void(0);"
                                               class="btn btn-sm btn-outline-secondary show-modal ms-1"
                                               data-size="md"
                                               data-title="Upload Document"
                                               data-action="{{ route('VisaExpense.fileupload', $data->id) }}">
                                                Upload
                                            </a>
                                            @endif
                                            @endcan
                                            @endif
                                        @endforelse
                                    </td>
                                </tr>
                                @if($data->payment_status == 'paid')
                                <tr>
                                    <th>Paid By</th>
                                    <td class="text-end">{{ company_table('accounts')->where('id' , $data->pay_account)->first()->name ?? '—' }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th></th>
                                    @if($data->payment_status == 'paid')
                                    <td class="text-end"><span class="btn btn-action btn-success">Paid</span></td>
                                    @elseif(!$hideActions)
                                    <td class="text-end">
                                        <a href="javascript:void(0);"
                                           class="btn btn-action btn-primary show-modal"
                                           data-action="{{ route('VisaExpense.payForm', $data->id) }}"
                                           data-size="xl"
                                           data-title="Pay Visa Expense">
                                            Proceed to Pay
                                        </a>
                                    </td>
                                    @else
                                    <td class="text-end"><span class="badge bg-warning text-dark">Unpaid</span></td>
                                    @endif
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
