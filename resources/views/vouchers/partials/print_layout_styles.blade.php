{{--
  Signature position + sheet flex helpers for voucher print docs.
  - Page bottom: pin signature row to the bottom of the sheet/page
  - After content: place signature row right under the last content block

  Header look/size/chips are applied by vouchers.partials.print_company_header.
  Expects $printLayout and/or $plPos already resolved from layoutPartsFor.
--}}
@php
    if (!isset($printLayout)) {
        $layoutCode = $printLayoutCode
            ?? $voucherTypeCode
            ?? ($voucherType ?? null)
            ?? ($payment->voucher->voucher_type ?? null)
            ?? ($voucher->voucher_type ?? null)
            ?? null;
        if (is_object($layoutCode) && isset($layoutCode->code)) {
            $layoutCode = $layoutCode->code;
        }
        $printLayout = $layoutCode
            ? app(\App\Services\Voucher\VoucherSignatureResolver::class)->layoutFor((string) $layoutCode)
            : null;
    }

    if (!isset($plPos) || !in_array($plPos, ['bottom', 'after'], true)) {
        // Match Voucher Signature Assignment default ("Page bottom").
        $plPos = ($printLayout['signature_position'] ?? 'bottom') === 'after'
            ? 'after'
            : 'bottom';
    }

    $pinBottom = $plPos === 'bottom';
    // Bottom pin needs a tall sheet so margin-top:auto has room — including print/PDF.
    // (Previously print forced min-height:0, which made "Page bottom" behave like "After content".)
    if ($pinBottom) {
        $sheetMinHeight = !empty($isPdf) ? '240mm' : '720px';
    } else {
        $sheetMinHeight = '0';
    }
@endphp
<style>
.rpv-doc .rpv-sheet {
    display: flex !important;
    flex-direction: column !important;
    min-height: {{ $sheetMinHeight }} !important;
}
.rpv-doc .rpv-sheet.rpv-sheet--sig-bottom,
.rpv-doc .rpv-sheet--sig-bottom {
    min-height: {{ $sheetMinHeight }} !important;
}
.rpv-doc .rpv-sheet.rpv-sheet--sig-after,
.rpv-doc .rpv-sheet--sig-after {
    min-height: 0 !important;
}

/* Push signatures to the page/sheet bottom */
.rpv-doc .rpv-sheet--sig-bottom > .vsa-sig-wrap,
.rpv-doc .rpv-sheet--sig-bottom > .rpv-sigs,
.rpv-doc .rpv-sheet--sig-bottom > .rpv-sig-push {
    margin-top: auto !important;
}
.rpv-doc .rpv-sheet--sig-bottom > .rpv-sig-push {
    flex: 1 1 auto !important;
    min-height: 24px;
    width: 100%;
}

/* Sit directly under the last content block */
.rpv-doc .rpv-sheet--sig-after > .vsa-sig-wrap,
.rpv-doc .rpv-sheet--sig-after > .rpv-sigs {
    margin-top: 28px !important;
}
.rpv-doc .rpv-sheet--sig-after > .rpv-sig-push {
    display: none !important;
}

@media print {
    .rpv-doc .rpv-sheet--sig-bottom {
        min-height: 240mm !important;
    }
    .rpv-doc .rpv-sheet--sig-after {
        min-height: 0 !important;
    }
}
</style>
