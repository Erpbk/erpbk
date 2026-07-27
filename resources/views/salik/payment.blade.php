@extends('layouts.app')
@section('title', 'Salik Payment')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h3>Salik Payment</h3></div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('salik.index') }}" class="btn btn-outline-primary">Back to Salik List</a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Company <span class="text-danger">*</span></label>
                    <select id="leasing_company_id" class="form-select select2" required>
                        <option value="">Select Bike company</option>
                        @php
                        $ownCompanyName = trim((string) (\App\Helpers\Common::getSetting('company_name') ?: ''));
                        if ($ownCompanyName === '') {
                            $currentCompany = view()->shared('currentCompany');
                            $ownCompanyName = is_object($currentCompany) ? trim((string) ($currentCompany->name ?? '')) : '';
                        }
                        if ($ownCompanyName === '') {
                            $ownCompanyName = 'Own Vehicles';
                        }
                        @endphp
                        <option value="own">{{ $ownCompanyName }}</option>
                        @foreach($leasingCompanies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Billing Month <span class="text-danger">*</span></label>
                    <input type="month" id="billing_month_filter" class="form-control" value="{{ date('Y-m') }}">
                </div>
                <div class="col-md-2 form-group d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" id="loadSalikRecords">Load Records</button>
                </div>
            </div>
        </div>
    </div>

    <form id="salikPaymentForm" action="{{ route('salik.payment.store') }}" method="POST">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><strong>Voucher Details</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Payment Date</label>
                        <input type="date" name="trans_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Billing Month</label>
                        <input type="month" name="billing_month" id="billing_month_voucher" class="form-control" value="{{ date('Y-m') }}" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Salik payment remarks">
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0" id="voucher_lines_table">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th>Narration</th>
                                <th class="text-end" style="width: 130px;">Debit</th>
                                <th class="text-end" style="width: 130px;">Credit</th>
                            </tr>
                        </thead>
                        <tbody id="voucher_lines_body">
                            <tr id="voucher_empty_row">
                                <td colspan="4" class="text-muted text-center py-3">Select salik records to preview voucher lines.</td>
                            </tr>
                        </tbody>
                        <tfoot id="voucher_totals_foot" style="display:none;">
                            <tr class="fw-bold table-light">
                                <td colspan="2" class="text-end">Total</td>
                                <td class="text-end" id="total_debit_amount">0.00</td>
                                <td class="text-end" id="total_credit_amount">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Unpaid Salik Records</strong>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllSaliks">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllSaliks">Deselect All</button>
                </div>
            </div>
            <div class="card-body table-responsive" id="payment-records-table">
                <p class="text-muted mb-0">Select leasing company and billing month, then click Load Records.</p>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary" id="submitPayment" disabled>Submit Payment</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
$(function () {
    $('.select2').select2({ allowClear: true, placeholder: 'Select Bike company' });

    $('#loadSalikRecords').on('click', function () {
        var billingMonth = $('#billing_month_filter').val();
        var leasingCompanyId = $('#leasing_company_id').val();
        if (!billingMonth) {
            alert('Please select billing month.');
            return;
        }
        if (!leasingCompanyId) {
            alert('Please select a leasing company.');
            return;
        }
        $('#billing_month_voucher').val(billingMonth);
        $.post('{{ route("salik.payment.records") }}', {
            _token: '{{ csrf_token() }}',
            billing_month: billingMonth,
            leasing_company_id: leasingCompanyId
        }, function (res) {
            $('#payment-records-table').html(res.html);
            bindSalikSelection();
        });
    });

    function bindSalikSelection() {
        $('.salik-checkbox').off('change').on('change', recalculateVoucher);
        $('#selectAllSaliks').off('click').on('click', function () {
            $('.salik-checkbox').prop('checked', true);
            recalculateVoucher();
        });
        $('#deselectAllSaliks').off('click').on('click', function () {
            $('.salik-checkbox').prop('checked', false);
            recalculateVoucher();
        });
    }

    var lastVoucherData = null;

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function formatAmount(value) {
        return parseFloat(value || 0).toFixed(2);
    }

    function renderVoucherLines(data) {
        var rowsHtml = '';

        rowsHtml += '<tr>' +
            '<td>' + escapeHtml(data.payable_account_name) + '</td>' +
            '<td>' + escapeHtml(data.payable_narration || '') + '</td>' +
            '<td class="text-end">' + formatAmount(data.payable_debit) + '</td>' +
            '<td class="text-end">—</td>' +
            '</tr>';

        if (parseFloat(data.vat_debit) > 0) {
            rowsHtml += '<tr>' +
                '<td>' + escapeHtml(data.vat_account_name) + '</td>' +
                '<td>' + escapeHtml(data.vat_narration || data.payable_narration || '') + '</td>' +
                '<td class="text-end">' + formatAmount(data.vat_debit) + '</td>' +
                '<td class="text-end">—</td>' +
                '</tr>';
        }

        (data.credit_lines || []).forEach(function (line) {
            rowsHtml += '<tr>' +
                '<td>' + escapeHtml(line.account_name) + '</td>' +
                '<td>' + escapeHtml(line.narration || '') + '</td>' +
                '<td class="text-end">—</td>' +
                '<td class="text-end">' + formatAmount(line.amount) + '</td>' +
                '</tr>';
        });

        $('#voucher_lines_body').html(rowsHtml);
        $('#total_debit_amount').text(formatAmount(data.total_debit));
        $('#total_credit_amount').text(formatAmount(data.total_credit));
        $('#voucher_totals_foot').show();
    }

    function clearVoucherLines() {
        lastVoucherData = null;
        $('#voucher_lines_body').html(
            '<tr id="voucher_empty_row">' +
            '<td colspan="4" class="text-muted text-center py-3">Select salik records to preview voucher lines.</td>' +
            '</tr>'
        );
        $('#voucher_totals_foot').hide();
        $('#total_debit_amount, #total_credit_amount').text('0.00');
    }

    function recalculateVoucher() {
        var ids = $('.salik-checkbox:checked').map(function () { return $(this).val(); }).get();
        $('#salikPaymentForm input[name="salik_ids[]"]').remove();
        ids.forEach(function (id) {
            $('#salikPaymentForm').append('<input type="hidden" name="salik_ids[]" value="' + id + '">');
        });

        if (!ids.length) {
            clearVoucherLines();
            $('#submitPayment').prop('disabled', true);
            return;
        }

        $.post('{{ route("salik.payment.calculate") }}', {
            _token: '{{ csrf_token() }}',
            salik_ids: ids,
            billing_month: $('#billing_month_voucher').val(),
            leasing_company_id: $('#leasing_company_id').val()
        }, function (data) {
            lastVoucherData = data;
            renderVoucherLines(data);
            $('#submitPayment').prop('disabled', !data.balanced);
        });
    }

    $('#salikPaymentForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function (res) {
                window.location.href = '{{ route("salik.index") }}';
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.errors?.error || xhr.responseJSON?.message || 'Payment failed');
            }
        });
    });
});
</script>
@endsection
