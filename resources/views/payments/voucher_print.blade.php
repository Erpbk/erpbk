<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Payment Voucher' }} - {{ $voucherNo ?? ($referenceNo ?? '') }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body {
            padding: 10px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        @page {
            size: A4;
            margin: 10mm;
        }
        @media print {
            body { padding: 0; }
            .voucher-posted-transactions,
            .no-print,
            .rpv-diff-row,
            .rpv-attachments { display: none !important; }
        }
    </style>
</head>
<body>
    @include('payments.partials.voucher_document_body')
    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
