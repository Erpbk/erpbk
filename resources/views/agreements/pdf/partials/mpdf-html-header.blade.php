{{-- mPDF repeating header (htmlpageheader). Inline styles: header trees do not inherit document CSS. --}}
@php
  $s = $branding['secondary_color'] ?? '#2563eb';
  $name = trim((string) ($branding['name'] ?? ''));
  $phone = trim((string) ($branding['phone'] ?? ''));
  $address = trim((string) ($branding['address'] ?? ''));
  $city = trim((string) ($branding['city'] ?? ''));
  $country = trim((string) ($branding['country'] ?? ''));
  $headerTop = (float) ($headerTopMarginMm ?? config('agreement_letterhead.header_top_margin_mm', 8));
  $logoMm = app(\App\Services\Agreements\AgreementPdfBranding::class)->letterheadLogoBoxMm();
  $logoW = (float) ($branding['logo_width_mm'] ?? $logoMm['width']);
  $logoH = (float) ($branding['logo_height_mm'] ?? $logoMm['height']);
  $logoPxW = (int) ($branding['logo_width_px'] ?? round($logoW * 96 / 25.4));
  $logoPxH = (int) ($branding['logo_height_px'] ?? round($logoH * 96 / 25.4));
  $addressLine = $address;
  if ($city !== '') {
      $addressLine .= ($addressLine !== '' ? ', ' : '').$city;
  }
  if ($country !== '') {
      $addressLine .= ($addressLine !== '' ? ', ' : '').$country;
  }
@endphp
<div style="width: 100%; padding-top: {{ $headerTop }}mm;">
  <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr>
      <td width="{{ $logoW }}mm" style="width: {{ $logoW }}mm; vertical-align: middle; border: 0; padding: 0; line-height: 0;">
        @include('agreements.pdf.partials.logo', [
          'branding' => $branding,
          'pdfEngine' => 'mpdf',
          'logoWidthMm' => $logoW,
          'logoHeightMm' => $logoH,
          'logoWidthPx' => $logoPxW,
          'logoHeightPx' => $logoPxH,
        ])
      </td>
      <td style="vertical-align: middle; text-align: left; border: 0; padding: 0 0 0 1.5mm;">
        @if($name !== '')
        <p style="margin: 0 0 1pt; font-size: 14pt; color: #0f172a; line-height: 1.3; text-align: left; font-weight: bold;">{{ $name }}</p>
        @endif
        @if($phone !== '')
        <p style="margin: 0 0 1pt; font-size: 11pt; color: #1e293b; line-height: 1.35; text-align: left; font-weight: bold;">{{ $phone }}</p>
        @endif
        @if($addressLine !== '')
        <p style="margin: 0 0 1pt; font-size: 11pt; color: #1e293b; line-height: 1.35; text-align: left; font-weight: bold;">{{ $addressLine }}</p>
        @endif
      </td>
    </tr>
  </table>
  <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-top: 2mm;">
    <tr>
      <td style="height: 0.5mm; line-height: 0.5mm; font-size: 1px; background-color: {{ $s }}; border: 0; padding: 0;">&nbsp;</td>
    </tr>
  </table>
</div>
