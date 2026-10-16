{{-- mPDF repeating header (htmlpageheader). Inline styles: header trees do not inherit document CSS. --}}
@php
  $primary = $branding['primary_color'] ?? '#004aad';
  $name = trim((string) ($branding['name'] ?? ''));
  $phone = trim((string) ($branding['phone'] ?? ''));
  $email = trim((string) ($branding['email'] ?? ''));
  $address = trim((string) ($branding['address'] ?? ''));
  $city = trim((string) ($branding['city'] ?? ''));
  $country = trim((string) ($branding['country'] ?? ''));
  $headerTop = (float) ($headerTopMarginMm ?? config('agreement_letterhead.header_top_margin_mm', 8));
  $logoMm = app(\App\Services\Agreements\AgreementPdfBranding::class)->letterheadLogoBoxMm();
  $logoW = min((float) ($branding['logo_width_mm'] ?? $logoMm['width']), 48.0);
  $logoH = min((float) ($branding['logo_height_mm'] ?? $logoMm['height']), 22.0);
  $logoPxW = (int) ($branding['logo_width_px'] ?? round($logoW * 96 / 25.4));
  $logoPxH = (int) ($branding['logo_height_px'] ?? round($logoH * 96 / 25.4));
  $addressLine = $address;
  if ($city !== '') {
      $addressLine .= ($addressLine !== '' ? ', ' : '').$city;
  }
  if ($country !== '') {
      $addressLine .= ($addressLine !== '' ? ', ' : '').$country;
  }
  $metaParts = [];
  if ($phone !== '') {
      $metaParts[] = 'Tel: '.$phone;
  }
  if ($email !== '') {
      $metaParts[] = $email;
  }
  $metaLine = implode(' · ', $metaParts);
@endphp
<div style="width: 100%; padding-top: {{ $headerTop }}mm;">
  <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr>
      <td width="22%" style="width: 22%; vertical-align: middle; border: 0; padding: 0 0 4.5mm 0; line-height: 0;">
        @include('agreements.pdf.partials.logo', [
          'branding' => $branding,
          'pdfEngine' => 'mpdf',
          'logoWidthMm' => $logoW,
          'logoHeightMm' => $logoH,
          'logoWidthPx' => $logoPxW,
          'logoHeightPx' => $logoPxH,
        ])
      </td>
      <td width="56%" style="width: 56%; vertical-align: middle; text-align: center; border: 0; padding: 0 2.5mm 4.5mm 2.5mm;">
        @if($name !== '')
        <div style="margin: 0 0 3.5pt; font-size: 14pt; color: {{ $primary }}; line-height: 1.3; text-align: center; font-weight: bold; letter-spacing: 0.2px;">{{ ucwords($name) }}</div>
        @endif
        @if($metaLine !== '' || $addressLine !== '')
        <div style="margin: 0; font-size: 9pt; color: #64748b; line-height: 1.7; text-align: center; font-weight: normal;">
          @if($metaLine !== '')
          {{ $metaLine }}
          @endif
          @if($addressLine !== '')
          @if($metaLine !== '')<br>@endif{{ ucwords($addressLine) }}
          @endif
        </div>
        @endif
      </td>
      <td width="22%" style="width: 22%; vertical-align: middle; border: 0; padding: 0 0 4.5mm 0;">&nbsp;</td>
    </tr>
  </table>
  {{-- Single full-width rule (table border-bottom splits across columns in mPDF). --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="height: 2.5px; line-height: 2.5px; font-size: 1px; background-color: {{ $primary }}; border: 0; padding: 0;">&nbsp;</td>
    </tr>
  </table>
</div>
