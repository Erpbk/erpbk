<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leasing Company Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
</head>

<body>
    <style>
        @include('rider_invoices.partials.invoice_brand_styles')

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body:has(.invoice-show-layout) {
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
        }

        .invoice-show-layout {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            background: #eef2f5;
        }

        body > .invoice-show-layout {
            height: 100vh;
            height: 100dvh;
        }

        #modalTopbody {
            overflow: hidden;
            padding: 0 !important;
        }

        #modalTopbody .invoice-show-layout {
            height: calc(100vh - 160px);
            max-height: 80vh;
        }

        #rightSideModalBody {
            overflow: hidden !important;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        #rightSideModalBody .invoice-show-layout {
            height: 100%;
            flex: 1;
        }

        .right-side-modal .modal-body {
            overflow: hidden !important;
        }

        .invoice-action-bar {
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #fff;
            border-bottom: 1px solid var(--inv-border);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .invoice-action-bar-inner {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px 20px;
        }

        .invoice-scroll-area {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 20px;
        }

        .invoice-box .primary-header,
        .invoice-box .secondary-header,
        .invoice-box .success-highlight {
            background: var(--inv-primary);
            color: var(--inv-on-primary);
            font-weight: 600;
        }

        .invoice-box .accent-total,
        .invoice-box .amount-highlight {
            background: var(--inv-secondary);
            color: var(--inv-on-primary);
            font-weight: 600;
        }

        .invoice-box .light-header {
            background: var(--inv-surface-soft);
            color: var(--inv-primary);
            font-weight: 600;
        }

        .invoice-box .label-cell {
            font-weight: 600;
            background: var(--inv-surface);
            color: var(--inv-text);
            width: 20%;
        }

        .invoice-box .value-cell {
            width: 30%;
        }

        .invoice-box .yellow {
            background: var(--inv-surface-soft);
            color: var(--inv-primary);
            font-weight: 600;
            padding: 3px 8px;
            display: inline-block;
            border: 1px solid var(--inv-border);
        }

        .invoice-box .red {
            color: var(--inv-secondary);
            font-weight: 600;
        }

        .invoice-box .footer-note {
            font-size: 11px;
            margin-top: 10px;
            color: var(--inv-text-muted);
            font-weight: normal;
            text-align: center;
        }

        .invoice-box .sign-box {
            margin-top: 25px;
            text-align: right;
            font-weight: 600;
            color: var(--inv-text);
        }

        .invoice-box .sign-box span {
            display: block;
            margin-top: 8px;
        }

        .invoice-box .summary-table {
            margin-bottom: 8px;
        }

        .invoice-toolbar {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
            max-width: none;
        }

        .invoice-toolbar-inner {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid var(--inv-border);
            border-radius: 6px;
            color: var(--inv-text);
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar-btn:hover {
            background: var(--inv-surface);
            color: var(--inv-primary);
            border-color: var(--inv-primary);
        }

        @media print {
            html,
            body {
                height: auto;
                overflow: visible;
            }

            body:has(.invoice-show-layout) {
                display: block;
                background: white;
            }

            .invoice-show-layout {
                height: auto;
                max-height: none;
            }

            .invoice-action-bar,
            .no-print {
                display: none !important;
            }

            .invoice-scroll-area {
                overflow: visible;
                padding: 0;
            }

            .invoice-box {
                width: 100%;
                border: none;
            }

            body,
            *,
            .primary-header,
            .secondary-header,
            .accent-total,
            .light-header,
            .amount-highlight,
            .success-highlight,
            .inv-total-row td,
            .inv-grand-total td,
            .inv-section-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 700px) {
            .invoice-scroll-area {
                padding: 10px;
            }

            .invoice-action-bar-inner {
                padding: 8px 12px;
            }

            .invoice-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="controls no-print">
    <button type="button" class="print-btn" onclick="printModalContent()">Print Invoice</button>
    @can('leasing_companies_invoices_edit')
    <a href="javascript:void(0);" data-size="xl" data-title="Edit Invoice" data-action="{{ route('leasingCompanyInvoices.edit', $invoice->id) }}" class="print-btn show-modal" style="text-decoration: none;">Edit</a>
    @endcan
</div>

<div class="invoice-box">
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $invoiceNumber = $invoice->invoice_number ?? ('LCI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT));
        $serviceFrom = date('d-m-y', strtotime($invoice->billing_month));
        $serviceTo = date('t-m-y', strtotime($invoice->billing_month));
        $fmo = strtoupper(date("M'y", strtotime($invoice->billing_month)));
        $subtotal = (float) ($invoice->subtotal ?? 0);
        $vatTotal = (float) ($invoice->vat ?? 0);
        $finalTotal = (float) ($invoice->total_amount ?? 0);
        $paidAmount = (float) ($invoice->paid_amount ?? 0);
        $balanceDue = $finalTotal - $paidAmount;
        $isPaid = (int) ($invoice->status ?? 0) === 1;
        $amountInWords = \App\Helpers\Helpers::numberToWords($finalTotal);
        $companySlug = request()->route('company_slug');
    @endphp

    <div class="invoice-show-layout">
        <div class="invoice-action-bar no-print">
            <div class="invoice-action-bar-inner">
                <div class="invoice-toolbar no-print">
                    <div class="invoice-toolbar-inner">
                        <a href="javascript:void(0);" class="toolbar-btn show-modal" data-size="xl" data-title="Edit Invoice" data-close-right-modal="1" data-action="{{ route('leasingCompanyInvoices.edit', $invoice->id) }}">
                            <i class="ti ti-edit"></i><span>Edit</span>
                        </a>
                        <a href="{{ route('leasingCompanyInvoices.show', $invoice->id) }}" class="toolbar-btn" target="_blank" rel="noopener">
                            <i class="ti ti-download"></i><span>Download</span>
                        </a>
                        <button type="button" class="toolbar-btn" onclick="printModalContent()">
                            <i class="ti ti-printer"></i><span>Print</span>
                        </button>
                        @if(! $isPaid)
                        <a href="javascript:void(0);" class="toolbar-btn show-modal" data-size="xl" data-title="Record Leasing Payment" data-action="{{ route('payments.create', ['company_slug' => $companySlug]) }}?leasing_company_id={{ $invoice->leasing_company_id }}&invoice_id={{ $invoice->id }}">
                            <i class="ti ti-currency-dollar"></i><span>Make Payment</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-scroll-area">
            <div class="invoice-box">
                <table class="no-border" width="100%">
                    <tr>
                        <td width="33.33%" class="no-border">
                            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                                <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
                            @else
                                <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
                            @endif
                        </td>
                        <td width="66.67%" class="no-border" style="text-align: center;">
                            <h4 style="margin-bottom: 10px; margin-top: 5px; font-size: 14px;">{{ ucwords($settings['company_name'] ?? '') }}</h4>
                            <p style="margin-bottom: 5px; font-size: 14px; margin-top: 5px;">{{ ucwords($settings['company_address'] ?? '') }}</p>
                            <p style="margin-bottom: 5px; font-size: 14px; margin-top: 5px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
                        </td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td colspan="4" class="primary-header" style="padding: 10px; text-align: center; font-size: 18px;">LEASING COMPANY INVOICE</td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td class="label-cell">Invoice No:</td>
                        <td class="value-cell">{{ $invoiceNumber }}</td>
                        <td class="label-cell">LC Invoice No:</td>
                        <td class="value-cell">{{ $invoice->leasing_company_invoice_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Invoice Date:</td>
                        <td class="value-cell">{{ optional($invoice->inv_date)->format('d/m/Y') ?? optional($invoice->created_at)->format('d/m/Y') }}</td>
                        <td class="label-cell">Reference No:</td>
                        <td class="value-cell">{{ $invoice->reference_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Service Period From:</td>
                        <td class="value-cell">{{ $serviceFrom }}</td>
                        <td class="label-cell">Service Period To:</td>
                        <td class="value-cell">{{ $serviceTo }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Billing Month:</td>
                        <td class="value-cell">{{ date('M-Y', strtotime($invoice->billing_month)) }}</td>
                        <td class="label-cell">Total Bikes:</td>
                        <td class="value-cell">{{ $invoice->items ? $invoice->items->count() : 0 }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Billed To:</td>
                        <td class="value-cell" colspan="3">{{ $settings['company_name'] ?? '—' }}</td>
                    </tr>
                </table>

                <table>
                    <tr>
                        <td colspan="4" class="light-header" style="padding: 8px; text-align: center; font-size: 14px;">LEASING COMPANY DETAILS</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Company Name:</td>
                        <td class="value-cell">{{ $invoice->leasingCompany->name ?? '—' }}</td>
                        <td class="label-cell">TRN Number:</td>
                        <td class="value-cell">{{ $invoice->leasingCompany->trn_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Contact Person:</td>
                        <td class="value-cell">{{ $invoice->leasingCompany->contact_person ?? '—' }}</td>
                        <td class="label-cell">Contact Number:</td>
                        <td class="value-cell">{{ $invoice->leasingCompany->contact_number ?? '—' }}</td>
                    </tr>
                    @if($invoice->descriptions)
                    <tr>
                        <td class="label-cell">Description:</td>
                        <td class="value-cell" colspan="3">{{ $invoice->descriptions }}</td>
                    </tr>
                    @endif
                </table>

                @if($invoice->items && $invoice->items->count() > 0)
                @php $runningTotal = 0; $exclTotal = 0; $taxTotal = 0; @endphp
                <table class="items-table">
                    <tr>
                        <th rowspan="2" class="secondary-header">Sr.</th>
                        <th rowspan="2" class="secondary-header">Product / Service Description</th>
                        <th rowspan="2" class="secondary-header">FMO</th>
                        <th rowspan="2" class="secondary-header">Qty</th>
                        <th rowspan="2" class="secondary-header">Days</th>
                        <th rowspan="2" class="secondary-header">Rate</th>
                        <th rowspan="2" class="secondary-header">Amount</th>
                        <th colspan="2" class="secondary-header">VAT</th>
                        <th rowspan="2" class="accent-total">Total (In {{ \App\Helpers\Currency::code() }})</th>
                    </tr>
                    <tr>
                        <th class="secondary-header">Rate</th>
                        <th class="secondary-header">Amount</th>
                    </tr>
                    @foreach($invoice->items as $key => $item)
                    @php
                        $vatRate = (float) ($item->tax_rate ?? 0);
                        $vatAmtRow = (float) ($item->tax_amount ?? 0);
                        $rowTotal = (float) ($item->total_amount ?? (($item->rental_amount ?? 0) + $vatAmtRow));
                        $exclAmount = $rowTotal - $vatAmtRow;
                        $runningTotal += $rowTotal;
                        $exclTotal += $exclAmount;
                        $taxTotal += $vatAmtRow;
                        $bike = $item->bike;
                        $plate = $bike->plate ?? 'N/A';
                        $emirates = $bike->emirates ?? '';
                    @endphp
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>Bike # {{ $plate }}@if($emirates) ({{ $emirates }})@endif</td>
                        <td>{{ $fmo }}</td>
                        <td class="num">1</td>
                        <td class="num">{{ $item->days ?? 1 }}</td>
                        <td class="num">{{ number_format($item->rental_amount ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($exclAmount, 2) }}</td>
                        <td>{{ number_format($vatRate, 0) }}%</td>
                        <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
                        <td class="num">{{ number_format($runningTotal, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="accent-total">
                        <td colspan="6" style="text-align: right; padding: 8px;">Total Bikes ({{ date('M-Y', strtotime($invoice->billing_month)) }})</td>
                        <td class="num">{{ $invoice->items->count() }}</td>
                        <td colspan="2" style="text-align: right; padding: 8px;">ITEMS TOTAL</td>
                        <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($runningTotal, 2) }}</td>
                    </tr>
                </table>

                <table class="no-border">
                    <tr>
                        <td class="amount-highlight" style="padding: 8px; font-size: 13px;">
                            <b>Total Invoice Amount in Words:</b> {{ $amountInWords }} {{ \App\Helpers\Currency::code() }}
                        </td>
                    </tr>
                </table>

                <table class="summary-table">
                    <tr class="light-header">
                        <td style="padding: 6px;">Total Amount before charges:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($subtotal ?: $exclTotal, 2) }}</td>
                    </tr>
                    @if(($vatTotal ?: $taxTotal) > 0)
                    <tr class="light-header">
                        <td style="padding: 6px;">Add: VAT</td>
                        <td class="num" style="padding: 6px;">{{ number_format($vatTotal ?: $taxTotal, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="success-highlight">
                        <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT AFTER CHARGES:</td>
                        <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($finalTotal ?: $runningTotal, 2) }}</td>
                    </tr>
                    <tr class="amount-highlight">
                        <td style="padding: 6px;">Paid Amount:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($paidAmount, 2) }}</td>
                    </tr>
                    <tr class="amount-highlight">
                        <td style="padding: 6px;">Balance Due:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($balanceDue, 2) }}</td>
                    </tr>
                </table>
                @else
                <p style="text-align: center; padding: 32px; color: var(--inv-text-muted);">No invoice items found for this period.</p>
                @endif

                <div class="footer-note">
                    {{ $invoice->notes ?? 'Note: This leasing company invoice is generated for the selected billing month. Please contact accounts for any discrepancies.' }}
                </div>

                <div class="sign-box">
                    For Leasing Company <br>
                    <span class="yellow">{{ $invoice->leasingCompany->name ?? '—' }}</span>
                    <span>### Sign</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            document.querySelectorAll('.num').forEach(function(el) {
                var raw = el.innerText.trim();
                var num = parseFloat(raw.replace(/,/g, ''));
                if (!isNaN(num) && raw !== '') {
                    var formatted = num.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    if (el.innerText !== formatted) el.innerText = formatted;
                }
            });
        })();
    </script>
</body>

</html>
