@php
$primary = $branding['primary_color'] ?? '#004aad';
$name = trim((string) ($branding['name'] ?? ''));
$phone = trim((string) ($branding['phone'] ?? ''));
$email = trim((string) ($branding['email'] ?? ''));
$address = trim((string) ($branding['address'] ?? ''));
$city = trim((string) ($branding['city'] ?? ''));
$country = trim((string) ($branding['country'] ?? ''));
$pdfEngine = $pdfEngine ?? 'html';
$logoMm = app(\App\Services\Agreements\AgreementPdfBranding::class)->letterheadLogoBoxMm();
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
<header class="page-header">
  <div class="page-header-inner">
    <table class="page-header-table" cellpadding="0" cellspacing="0">
      <tr>
        <td class="page-header-logo">
          @include('agreements.pdf.partials.logo', [
            'branding' => $branding,
            'pdfEngine' => $pdfEngine,
            'logoHeightMm' => min((float) $logoMm['height'], 22.0),
            'logoWidthMm' => min((float) $logoMm['width'], 48.0),
          ])
        </td>
        <td class="page-header-brand">
          @if($name !== '')
          <div class="page-header-name">{{ ucwords($name) }}</div>
          @endif
          @if($metaLine !== '' || $addressLine !== '')
          <div class="page-header-meta">
            @if($metaLine !== '')
            {{ $metaLine }}
            @endif
            @if($addressLine !== '')
            @if($metaLine !== '')<br>@endif{{ ucwords($addressLine) }}
            @endif
          </div>
          @endif
        </td>
        <td class="page-header-spacer" aria-hidden="true">&nbsp;</td>
      </tr>
    </table>
  </div>
  <div class="page-header-rule" style="background-color: {{ $primary }};"></div>
</header>
