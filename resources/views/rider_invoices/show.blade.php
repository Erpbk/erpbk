<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RiderID: {{ $riderInvoice->rider?->rider_id ?? $riderInvoice->id }} Month: {{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</title>
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

        body:has(.rider-invoice-layout) {
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
        }

        .rider-invoice-layout {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            background: #eef2f5;
        }

        body > .rider-invoice-layout {
            height: 100vh;
            height: 100dvh;
        }

        #modalTopbody {
            overflow: hidden;
            padding: 0 !important;
        }

        #modalTopbody .rider-invoice-layout {
            height: calc(100vh - 160px);
            max-height: 80vh;
        }

        #rightSideModalBody {
            overflow: hidden !important;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        #rightSideModalBody .rider-invoice-layout {
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

        .template-selector {
            max-width: none;
            margin: 8px 0 0;
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
        }

        @media print {
            html,
            body {
                height: auto;
                overflow: visible;
            }

            body:has(.rider-invoice-layout) {
                display: block;
                background: white;
            }

            .rider-invoice-layout {
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
            .yellow {
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

    <div class="rider-invoice-layout">
        <div class="invoice-action-bar no-print">
            <div class="invoice-action-bar-inner">
                @include('rider_invoices.partials.action_buttons')
            </div>
        </div>

        <div class="invoice-scroll-area">
            <div class="invoice-box">
                @if(View::exists($templateView))
                    @include($templateView)
                @else
                    <div class="p-4 text-center text-danger">Invoice template view is missing on the server.</div>
                @endif
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

            var templateForm = document.getElementById('riderInvoiceTemplateForm');
            var templateSelect = document.getElementById('template_id');
            if (templateForm && templateSelect) {
                templateSelect.addEventListener('change', function(e) {
                    e.preventDefault();
                    fetch(templateForm.action, {
                            method: 'POST',
                            body: new FormData(templateForm),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).then(function(res) {
                            return res.json();
                        })
                        .then(function(data) {
                            if (data.reload) window.location.reload();
                        }).catch(function() {
                            templateForm.submit();
                        });
                });
            }
        })();
    </script>
</body>

</html>
