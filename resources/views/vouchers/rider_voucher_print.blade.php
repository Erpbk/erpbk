<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle ?? 'Voucher' }} - {{ $voucherNo ?? '' }}</title>
    <style>
        body { margin: 0; padding: 12px; background: #fff; }
        @media print {
            body { padding: 0; }
            .voucher-posted-transactions, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @include('vouchers.partials.rider_voucher_document_body', array_merge($doc ?? [], ['isPdf' => false]))
    @if(request()->boolean('print') || request()->get('print'))
    <script>window.onload = function () { window.print(); };</script>
    @endif
</body>
</html>
