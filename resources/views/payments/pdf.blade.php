<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle ?? 'PAYMENT VOUCHER' }} - {{ $referenceNo ?? '' }}</title>
</head>
<body>
    @include('payments.partials.voucher_document_body', ['isPdf' => true])
</body>
</html>
