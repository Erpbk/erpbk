<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Plan Invoice - {{ $rider->name }}</title>
    @include('installments.partials.installment_invoice_styles')
</head>

<body>
    @include('installments.partials.installment_invoice_body')

    <script>
        window.onload = function() {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        };
    </script>
</body>

</html>
