<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Invoice {{ $riderInvoice->invoice_number ?? $riderInvoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #004aad; color: #fff; }
        td.num { text-align: right; }
        .no-border td { border: none; }
        .accent-total { background: #5271ff; color: #fff; font-weight: bold; }
        .secondary-header { background: #004aad; color: #fff; }
        .success-highlight { background: #004aad; color: #fff; }
    </style>
</head>
<body>
@if(View::exists($templateView))
    @include($templateView)
@endif
</body>
</html>
