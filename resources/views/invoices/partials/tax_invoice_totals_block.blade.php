@php
    $subtotalAmount = $subtotalAmount ?? 0;
    $vatAmount = $vatAmount ?? 0;
    $totalAmount = $totalAmount ?? 0;
    $currency = $currency ?? \App\Helpers\Currency::code();
    $showPaidBalance = isset($paidAmount) || isset($balanceAmount);
    $pdfBlue = ($brand['primary_color'] ?? null) ?: '#004aad';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:10px;">Subtotal (excl. VAT)</td>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#0f172a; font-size:10px; font-weight:700; text-align:right;">{{ number_format((float) $subtotalAmount, 2) }}</td>
    </tr>
    @if((float) $vatAmount != 0)
    <tr>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:10px;">VAT Amount</td>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#0f172a; font-size:10px; font-weight:700; text-align:right;">{{ number_format((float) $vatAmount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td colspan="2" style="padding-top:6px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; background:{{ $pdfBlue }};">
                <tr>
                    <td style="padding:10px 12px; color:#ffffff; font-size:10px; font-weight:700; text-transform:uppercase;">Total Due</td>
                    <td style="padding:10px 12px; color:#ffffff; font-size:14px; font-weight:800; text-align:right;">{{ number_format((float) $totalAmount, 2) }} {{ $currency }}</td>
                </tr>
            </table>
        </td>
    </tr>
    @if($showPaidBalance)
    <tr>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:10px;">Paid Amount</td>
        <td style="padding:6px 10px; border-bottom:1px solid #e2e8f0; color:#0f172a; font-size:10px; font-weight:700; text-align:right;">{{ number_format((float) ($paidAmount ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="padding:6px 10px; color:#64748b; font-size:10px;">Balance</td>
        <td style="padding:6px 10px; color:#0f172a; font-size:10px; font-weight:700; text-align:right;">{{ number_format((float) ($balanceAmount ?? 0), 2) }}</td>
    </tr>
    @endif
</table>
