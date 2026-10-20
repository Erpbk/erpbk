<script src="{{ asset('js/modal_custom.js') }}"></script>

@php
if(isset($vouchers->voucher_type)){
$voucherType = $vouchers->voucher_type;
}else{
$voucherType = $vt ?? request('vt');
}
@endphp
<input type="hidden" name="v_trans_code" value="{{@$vouchers->trans_code??0}}">
<input type="hidden" name="voucher_type" id="voucher_type" value="{{$voucherType}}">

@php
$voucherTypeLabel = \App\Helpers\General::voucherType($voucherType);
$currencyCode = \App\Helpers\Currency::code();
@endphp

@if($voucherType == 'TRF')
    @include('vouchers.transfer_fields')
@else
<div class="uvf-form"
    data-uvf-journal="1"
    data-modal-title="{{ $voucherTypeLabel }}"
    data-modal-subtitle="Post balanced debit and credit lines">

    <div class="rpf-header" data-rpf-inline-header>
        <h4 class="rpf-title">{{ $voucherTypeLabel }}</h4>
        <p class="rpf-subtitle">Post balanced debit and credit lines</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="trans_date">Date <span class="text-danger">*</span></label>
                    <input type="date" name="trans_date" id="trans_date" class="form-control rpf-input" placeholder="Transaction Date" value="@isset($vouchers->trans_date){{date('Y-m-d',strtotime($vouchers->trans_date)) }}@else{{date('Y-m-d')}}@endisset">
                </div>

                @if (!in_array($voucherType, ['VC', 'COD', 'PN', 'INC', 'PAY'], true))
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="payment_type">Payment Type</label>
                    {!! Form::select('payment_type', App\Helpers\Account::payment_type_list(), null, ['class' => 'form-select select2 rpf-input', 'id' => 'payment_type']) !!}
                </div>
                @endif

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="billing_month">Billing Month <span class="text-danger">*</span></label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control rpf-input" value="@isset($vouchers->billing_month){{date('Y-m',strtotime($vouchers->billing_month)) }}@endisset" required>
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="reference_number">Reference <span class="text-danger">*</span></label>
                    <input type="text" name="reference_number" class="form-control rpf-input" id="reference_number" value="@isset($vouchers->reference_number){{$vouchers->reference_number}}@endisset" placeholder="Reference Number" required>
                </div>

                @if(isset($rider) && $rider)
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="branch_id">Branch</label>
                    @php
                        $branchid = $rider->branch_id ?? null;
                        $branch = auth()->user()->branchByid($branchid);
                    @endphp
                    <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                    <input type="text" class="form-control rpf-input bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
                </div>
                @elseif(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="branch_id">Branch</label>
                    <select class="form-control select2 rpf-input" id="branch_id" name="branch_id" required>
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }} @isset($vouchers->branch_id){{ $vouchers->branch_id == $id ? 'selected' : ''}} @endisset>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="branch_id">Branch</label>
                    @php
                        $branchids = app('user_branches') ?? [];
                        $branchid = $branchids[0] ?? null;
                        $branch = auth()->user()->branchByid($branchid);
                    @endphp
                    <input type="hidden" name="branch_id" value="{{ $branchid }}" />
                    <input type="text" class="form-control rpf-input bg-light" value="{{ $branch ? $branch->name : 'No Branch Found' }}" readonly />
                </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="rpf-attach-box">
                @include('partials.universal_document_upload', [
                    'name' => 'attachment',
                    'label' => null,
                    'required' => false,
                    'inputClass' => 'form-control',
                    'showHint' => true,
                    'variant' => 'dropzone',
                    'uploadLabel' => 'Upload File',
                    'existingUrl' => !empty($vouchers?->attach_file) ? url('storage/vouchers/' . ltrim($vouchers->attach_file, '/')) : null,
                    'existingName' => !empty($vouchers?->attach_file) ? basename($vouchers->attach_file) : null,
                ])
                <div class="rpf-attach-hint">Multiple pages combined into one PDF.</div>
            </div>
        </div>
    </div>

    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Ledger Lines</h6>

        @if($voucherType == 'JV' || $voucherType == 'RFV')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields")
        @endif

        @if($voucherType == 'LV')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields")
        @endif

        @if($voucherType == 'VL')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.visaloan_fields")
        @endif

        @if($voucherType == 'AL')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif

        @if($voucherType == 'COD')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif

        @if($voucherType == 'PN')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif

        @if($voucherType == 'INC')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif

        @if($voucherType == 'PAY')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif

        @if($voucherType == 'VC')
        @php($accounts = \App\Models\Accounts::dropdown(null))
        @include("vouchers.default_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
        @endif
    </div>

    <div class="rpf-section mt-3">
        <div class="row g-3">
            <div class="col-lg-7"></div>
            <div class="col-lg-5">
                <div class="rpf-summary">
                    <div class="rpf-summary-row">
                        <span>Total Dr:</span>
                        <span>{{ $currencyCode }} <span id="uvf_total_dr_display">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Total Cr:</span>
                        <span>{{ $currencyCode }} <span id="uvf_total_cr_display">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Difference:</span>
                        <span id="uvf_diff_display" class="text-success">{{ $currencyCode }} 0.00</span>
                    </div>
                    <div class="rpf-summary-total">
                        Balanced when difference is 0.00
                    </div>
                </div>
                <input type="hidden" class="form-control" id="total_dr" readonly>
                <input type="hidden" class="form-control" id="total_cr" readonly>
            </div>
        </div>
    </div>
</div>
@include('partials.uvf_styles')
@endif

@include('vouchers._custom_fields_section')

<script>
    /*  $(document).ready(function() {
  $('#datepicker').datepicker({
    format: "mm-yyyy", // Output format
    minView: 1, // Start with month view
    startView: 1, // Open in month view
  });
}); */

    // Wait for jQuery to be available
    (function() {
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery not loaded yet, waiting...');
            setTimeout(arguments.callee, 50);
            return;
        }

        window.getTotal = function getTotal() {
            var $scope = $('#formajax').length ? $('#formajax') : $(document);
            var cr_sum = 0;
            var dr_sum = 0;
            $scope.find(".cr_amount").each(function() {
                if (!isNaN(this.value) && this.value.length != 0) {
                    cr_sum += parseFloat(this.value);
                }
            });
            $scope.find(".dr_amount").each(function() {
                if (!isNaN(this.value) && this.value.length != 0) {
                    dr_sum += parseFloat(this.value);
                }
            });
            $scope.find(".amount").each(function() {
                if (!isNaN(this.value) && this.value.length != 0) {
                    cr_sum += parseFloat(this.value);
                }
            });
            // Round once so displayed totals match server money comparison.
            cr_sum = Math.round((cr_sum + Number.EPSILON) * 100) / 100;
            dr_sum = Math.round((dr_sum + Number.EPSILON) * 100) / 100;
            $scope.find("#total_cr").val(cr_sum.toFixed(2));
            $scope.find("#total_dr").val(dr_sum.toFixed(2));
            $scope.find("#uvf_total_dr_display").text(dr_sum.toFixed(2));
            $scope.find("#uvf_total_cr_display").text(cr_sum.toFixed(2));
            var diff = Math.round((dr_sum - cr_sum + Number.EPSILON) * 100) / 100;
            var currency = @json($currencyCode ?? \App\Helpers\Currency::code());
            var $diff = $scope.find("#uvf_diff_display");
            if ($diff.length) {
                $diff
                    .text(currency + ' ' + Math.abs(diff).toFixed(2))
                    .toggleClass('text-success', Math.abs(diff) < 0.005)
                    .toggleClass('text-danger', Math.abs(diff) >= 0.005);
            }
        };

        window.fetch_invoices = function fetch_invoices(g) {
            let id = g;
            var vt = $("#invoice_voucher_type").val();
            $.ajax({
                url: "{{ url('fetch_invoices') }}/" + id + '/' + vt,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    $("table.order-list").html(data.htmlData);
                    $("#riderBalance").val(data.rider_balance);
                    $("#vendor_balance").val(data.vendor_balance);
                }
            });
        };

        function getVoucherSelect2DropdownParent($select) {
            var $offcanvas = $select.closest('.offcanvas');
            if ($offcanvas.length) {
                return $offcanvas;
            }
            var $modal = $select.closest('.modal');
            if ($modal.length) {
                return $modal;
            }
            var $form = $('#formajax');
            if ($form.length) {
                return $form;
            }
            return $(document.body);
        }

        window.initVoucherSelect2 = function initVoucherSelect2($container) {
            if (!$.fn.select2) {
                return;
            }
            var $root = ($container && $container.length) ? $container : $('#formajax');
            if (!$root.length) {
                $root = $(document);
            }
            $root.find('select.select2').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                $select.select2({
                    width: '100%',
                    allowClear: true,
                    dropdownParent: getVoucherSelect2DropdownParent($select)
                });
            });
        };

        $(document).ready(function() {
            var base_url = $('#base_url').val();
            getTotal();
            initVoucherSelect2();
            if (typeof window.initFundsTransferForm === 'function') {
                window.initFundsTransferForm(document.getElementById('formajax'));
            }





            var counter = 0;
            $("#RID").on("change", function() {
                var id = $('#RID').val();
                var type = 5;

                $.get(base_url + '/get_invoice_balance?id=' + id + '&type=' + type).done(function(data) {
                    $("#riderInvoiceBalance").val(data.invoice_balance);
                    $("#riderBalance").val(data.balance);
                    $("#inv_id").val(data.inv_id);
                });
            });

            $("#addRiderRow").on("click", function() {
                var item_id = $('#RID').val();
                var item_name = $('#RID option:selected').text();
                var item_price = $('#riderAmount').val();
                var narration = $('#narration').val();
                var inv_id = $('#inv_id').val();
                var invoice = '';
                if (item_price != '' && item_id != '') {

                    var newRow = '<tr style="padding:5px;"><td width="200"><label>' + item_name + '</label><input type="hidden" name="id[]" value="' + item_id + '" /><input type="hidden" name="inv_id[]" value="' + inv_id + '" /></td>';
                    newRow += '<td width="200"><input type="text" name="narration[]"  value="' + narration + '" class="form-control " /></td>';
                    newRow += '<td width="100"><input type="number" step="any" name="amount[]"  value="' + item_price + '" step="any" class="form-control  amount" onchange="getTotal();" /></td>';
                    newRow += '<td width="100"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger "  value="Delete"></td></tr>';

                    $("table.order-list").append(newRow);
                    $('#riderAmount').val('');
                    $('#narration').val('');
                    $('#RID option:selected').text('select');
                    $('#RID').val(0);
                    $('#riderBalance').val('');
                    counter++;

                } else {
                    alert("Select Rider and Amount");
                }
                getTotal();
            });


            $("table.order-list").on("click", ".ibtnDel", function(event) {
                $(this).closest("tr").remove();
                counter -= 1;
                getTotal();
            });

            $(".cr_amount").on("focus keyup change", function() {
                getTotal();
            });
            $(".dr_amount").on("focus keyup change", function() {
                getTotal();
            });
            $(".amount").on("focus keyup change", function() {
                getTotal();
            });

            $(document).on('click', '#add-new-row', function() {
                setTimeout(function() {
                    initVoucherSelect2($('#rows-container .row:last, #rows-container tr.uvf-line-row:last').last());
                }, 0);
            });
        }); // End of $(document).ready

        $(document).on("change", "#invoice_voucher_type", function() {
            let thisVal = $(this).val();

            if (thisVal == 5) {
                $("#vendor_section").hide();
                $("#rider_section").show();
                $("table.order-list").html('');
            } else {
                $("#vendor_section").show();
                $("#rider_section").hide();
                $("table.order-list").html('');
            }
            getTotal();
        });

    })(); // End of jQuery check wrapper
</script>