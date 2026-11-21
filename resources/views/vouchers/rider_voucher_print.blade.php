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
    @include(
        !empty($isVisaExpenseDocument)
            ? 'vouchers.partials.visa_expense_voucher_document_body'
            : 'vouchers.partials.rider_voucher_document_body',
        array_merge($doc ?? [], ['isPdf' => true])
    )
    @if(request()->boolean('print') || request()->get('print'))
    <script>
      (function () {
        function whenImagesReady(done) {
          var imgs = Array.prototype.slice.call(document.images || []);
          if (!imgs.length) { done(); return; }
          var left = imgs.length;
          var finish = function () { left -= 1; if (left <= 0) done(); };
          imgs.forEach(function (img) {
            if (img.complete) { finish(); return; }
            img.addEventListener('load', finish, { once: true });
            img.addEventListener('error', finish, { once: true });
          });
          setTimeout(done, 2500);
        }
        window.addEventListener('load', function () {
          whenImagesReady(function () { window.print(); });
        });
      })();
    </script>
    @endif
</body>
</html>
