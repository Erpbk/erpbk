<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Invoice {{ $riderInvoice->invoice_number ?? $riderInvoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        td.num { text-align: right; }
        .no-border td { border: none; }
        .label-cell { font-weight: bold; width: 20%; }
        .value-cell { width: 30%; }
        .red { color: #c00; font-weight: bold; }
        .footer-note { font-size: 10px; margin-top: 10px; text-align: center; }
        .sign-box { margin-top: 25px; text-align: right; font-weight: bold; }
        .sign-box span { display: block; margin-top: 8px; }
        .yellow { font-weight: bold; }

        /* Classic / brand defaults */
        th,
        .primary-header,
        .secondary-header,
        .success-highlight { background: #004aad; color: #fff; font-weight: bold; }
        .accent-total,
        .amount-highlight { background: #5271ff; color: #fff; font-weight: bold; }
        .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }

        /* Modern salary-slip look (matches on-screen image) */
        .invoice-layout-modern th,
        .invoice-layout-modern .primary-header,
        .invoice-layout-modern .secondary-header,
        .invoice-layout-modern .light-header,
        .invoice-layout-modern .accent-total,
        .invoice-layout-modern .success-highlight,
        .invoice-layout-modern .amount-highlight {
            background: #c6d9f1;
            color: #000;
            font-weight: bold;
        }
        .invoice-layout-modern th,
        .invoice-layout-modern td { border-color: #b8c4d4; }
        .invoice-layout-modern .label-cell,
        .invoice-layout-modern .value-cell { background: #fff; color: #000; }
    </style>
</head>
<body>
<div class="invoice-layout-{{ $activeTemplate?->layout_key ?? 'modern' }}">
@if(View::exists($templateView))
    @include($templateView)
@endif
</div>
</body>
</html>
