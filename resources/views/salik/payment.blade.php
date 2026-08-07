@extends('layouts.app')
@section('title', 'Salik Payment')
@section('content')
@php
    $defaultFrom = now()->startOfMonth()->format('Y-m-d');
    $defaultTo = now()->endOfMonth()->format('Y-m-d');
@endphp
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
                <div class="col-md-2 form-group">
                    <label>From Date <span class="text-danger">*</span></label>
                    <input type="date" id="date_from" class="form-control" value="{{ $defaultFrom }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>To Date <span class="text-danger">*</span></label>
                    <input type="date" id="date_to" class="form-control" value="{{ $defaultTo }}">
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Voucher Details</strong>
                <button type="submit" class="btn btn-primary" id="submitPayment" disabled>Submit Payment</button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Payment Date</label>
                        <input type="date" name="trans_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Billing Month <span class="text-danger">*</span></label>
                        <input type="month" name="billing_month" id="billing_month_voucher" class="form-control" value="{{ date('Y-m') }}" required>
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
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Unpaid Salik Records <span id="selectedCountBadge" class="badge bg-primary ms-1" style="display:none;">0 selected</span></strong>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="text" id="salikRecordsSearch" class="form-control form-control-sm" style="min-width: 220px;" placeholder="Search transaction / plate / rider...">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectPageSaliks">Select Page</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectPageSaliks">Deselect Page</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllSaliks">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="deselectAllSaliks">Deselect All</button>
                </div>
            </div>
            <div class="card-body table-responsive" id="payment-records-table">
                <p class="text-muted mb-0">Select company and date range, then click Load Records.</p>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
<script>
$(function () {
    $('.select2').select2({ allowClear: true, placeholder: 'Select Bike company' });

    var selectedSalikIds = new Set();
    var searchTimer = null;
    var recordsLoaded = false;
    var currentPerPage = 50;

    function getSelectedIds() {
        return Array.from(selectedSalikIds);
    }

    function updateSelectedBadge() {
        var count = selectedSalikIds.size;
        if (count > 0) {
            $('#selectedCountBadge').text(count + ' selected').show();
        } else {
            $('#selectedCountBadge').hide();
        }
    }

    function syncHiddenInputs() {
        $('#salikPaymentForm input[name="salik_ids[]"]').remove();
        getSelectedIds().forEach(function (id) {
            $('#salikPaymentForm').append('<input type="hidden" name="salik_ids[]" value="' + id + '">');
        });
    }

    function restoreSelectionToDom() {
        $('.salik-checkbox').each(function () {
            $(this).prop('checked', selectedSalikIds.has(String($(this).val())));
        });
        var allChecked = $('.salik-checkbox').length > 0 && $('.salik-checkbox:not(:checked)').length === 0;
        $('#checkAllSaliks').prop('checked', allChecked);
    }

    function loadRecords(page) {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();
        var leasingCompanyId = $('#leasing_company_id').val();
        if (!dateFrom || !dateTo) {
            alert('Please select From and To dates.');
            return;
        }
        if (dateFrom > dateTo) {
            alert('From date cannot be after To date.');
            return;
        }
        if (!leasingCompanyId) {
            alert('Please select a leasing company.');
            return;
        }

        var $btn = $('#loadSalikRecords');
        $btn.prop('disabled', true).text('Loading...');

        $.post('{{ route("salik.payment.records") }}', {
            _token: '{{ csrf_token() }}',
            date_from: dateFrom,
            date_to: dateTo,
            leasing_company_id: leasingCompanyId,
            search: $('#salikRecordsSearch').val() || '',
            page: page || 1,
            per_page: currentPerPage
        })
        .done(function (res) {
            recordsLoaded = true;
            $('#payment-records-table').html(res.html);
            bindSalikSelection();
            restoreSelectionToDom();
            updateSelectedBadge();
            syncHiddenInputs();
        })
        .fail(function (xhr) {
            var msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.date_to?.[0] || 'Failed to load records.';
            alert(msg);
        })
        .always(function () {
            $btn.prop('disabled', false).text('Load Records');
        });
    }

    $('#loadSalikRecords').on('click', function () {
        selectedSalikIds.clear();
        clearVoucherLines();
        $('#submitPayment').prop('disabled', true);
        updateSelectedBadge();
        loadRecords(1);
    });

    $('#salikRecordsSearch').on('keyup', function (e) {
        if (!recordsLoaded && e.keyCode !== 13) {
            return;
        }
        clearTimeout(searchTimer);
        var delay = e.keyCode === 13 ? 0 : 400;
        searchTimer = setTimeout(function () {
            loadRecords(1);
        }, delay);
    });

    $(document).on('click', '#paymentRecordsPagination a', function (e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (!href || href === '#') {
            return;
        }
        var page = 1;
        try {
            var url = new URL(href, window.location.origin);
            page = parseInt(url.searchParams.get('page') || '1', 10) || 1;
        } catch (err) {
            var match = href.match(/[?&]page=(\d+)/);
            page = match ? parseInt(match[1], 10) : 1;
        }
        loadRecords(page);
    });

    $(document).on('change', '#paymentRecordsPagination #perPageSelect', function (e) {
        e.preventDefault();
        e.stopPropagation();
        currentPerPage = $(this).val() || 50;
        loadRecords(1);
        return false;
    });

    function currentFilterPayload() {
        return {
            _token: '{{ csrf_token() }}',
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            leasing_company_id: $('#leasing_company_id').val(),
            search: $('#salikRecordsSearch').val() || ''
        };
    }

    function bindSalikSelection() {
        $('.salik-checkbox').off('change').on('change', function () {
            var id = String($(this).val());
            if ($(this).is(':checked')) {
                selectedSalikIds.add(id);
            } else {
                selectedSalikIds.delete(id);
            }
            updateSelectedBadge();
            recalculateVoucher();
        });

        $('#checkAllSaliks').off('change').on('change', function () {
            var checked = $(this).is(':checked');
            $('.salik-checkbox').each(function () {
                $(this).prop('checked', checked);
                var id = String($(this).val());
                if (checked) selectedSalikIds.add(id);
                else selectedSalikIds.delete(id);
            });
            updateSelectedBadge();
            recalculateVoucher();
        });

        $('#selectPageSaliks').off('click').on('click', function () {
            $('.salik-checkbox').each(function () {
                $(this).prop('checked', true);
                selectedSalikIds.add(String($(this).val()));
            });
            $('#checkAllSaliks').prop('checked', true);
            updateSelectedBadge();
            recalculateVoucher();
        });

        $('#deselectPageSaliks').off('click').on('click', function () {
            $('.salik-checkbox').each(function () {
                $(this).prop('checked', false);
                selectedSalikIds.delete(String($(this).val()));
            });
            $('#checkAllSaliks').prop('checked', false);
            updateSelectedBadge();
            recalculateVoucher();
        });

        $('#selectAllSaliks').off('click').on('click', function () {
            if (!recordsLoaded) {
                alert('Please load records first.');
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Selecting...');
            $.post('{{ route("salik.payment.recordIds") }}', currentFilterPayload())
                .done(function (res) {
                    selectedSalikIds = new Set((res.ids || []).map(String));
                    restoreSelectionToDom();
                    updateSelectedBadge();
                    recalculateVoucher();
                })
                .fail(function (xhr) {
                    alert(xhr.responseJSON?.message || 'Failed to select all records.');
                })
                .always(function () {
                    $btn.prop('disabled', false).text('Select All');
                });
        });

        $('#deselectAllSaliks').off('click').on('click', function () {
            selectedSalikIds.clear();
            $('.salik-checkbox').prop('checked', false);
            $('#checkAllSaliks').prop('checked', false);
            updateSelectedBadge();
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

    function syncNarrationsFromTop() {
        var base = ($('#payable_narration').val() || '').trim();
        $('#vat_narration').val(base ? ('( Vat ) ' + base.replace(/^\(\s*Vat\s*\)\s*/i, '')) : '');
        $('.credit-narration').val(base);
    }

    function renderVoucherLines(data) {
        var rowsHtml = '';

        rowsHtml += '<tr>' +
            '<td>' + escapeHtml(data.payable_account_name) + '</td>' +
            '<td><input type="text" class="form-control form-control-sm" name="payable_narration" id="payable_narration" value="' + escapeHtml(data.payable_narration || '') + '"></td>' +
            '<td class="text-end">' + formatAmount(data.payable_debit) + '</td>' +
            '<td class="text-end">—</td>' +
            '</tr>';

        if (parseFloat(data.vat_debit) > 0) {
            rowsHtml += '<tr>' +
                '<td>' + escapeHtml(data.vat_account_name) + '</td>' +
                '<td><input type="text" class="form-control form-control-sm" name="vat_narration" id="vat_narration" value="' + escapeHtml(data.vat_narration || '') + '" readonly></td>' +
                '<td class="text-end">' + formatAmount(data.vat_debit) + '</td>' +
                '<td class="text-end">—</td>' +
                '</tr>';
        }

        (data.credit_lines || []).forEach(function (line) {
            rowsHtml += '<tr>' +
                '<td>' + escapeHtml(line.account_name) + '</td>' +
                '<td><input type="text" class="form-control form-control-sm credit-narration" name="credit_narrations[]" value="' + escapeHtml(line.narration || '') + '" readonly></td>' +
                '<td class="text-end">—</td>' +
                '<td class="text-end">' + formatAmount(line.amount) + '</td>' +
                '</tr>';
        });

        $('#voucher_lines_body').html(rowsHtml);
        $('#total_debit_amount').text(formatAmount(data.total_debit));
        $('#total_credit_amount').text(formatAmount(data.total_credit));
        $('#voucher_totals_foot').show();
    }

    $(document).on('input', '#payable_narration', function () {
        syncNarrationsFromTop();
    });

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
        var ids = getSelectedIds();
        syncHiddenInputs();

        if (!ids.length) {
            clearVoucherLines();
            $('#submitPayment').prop('disabled', true);
            return;
        }

        if (!$('#billing_month_voucher').val()) {
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
        }).fail(function (xhr) {
            clearVoucherLines();
            $('#submitPayment').prop('disabled', true);
            alert(xhr.responseJSON?.error || xhr.responseJSON?.message || 'Unable to calculate voucher.');
        });
    }

    $('#billing_month_voucher').on('change', function () {
        if (selectedSalikIds.size > 0) {
            recalculateVoucher();
        }
    });

    $('#salikPaymentForm').on('submit', function (e) {
        e.preventDefault();
        syncHiddenInputs();
        if (!getSelectedIds().length) {
            alert('Please select at least one salik record.');
            return;
        }
        if (!$('#billing_month_voucher').val()) {
            alert('Please select a billing month for the voucher.');
            return;
        }
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function () {
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
