@php
$s = $branding['secondary_color'] ?? '#2563eb';
$name = trim((string) ($branding['name'] ?? ''));
$phone = trim((string) ($branding['phone'] ?? ''));
$address = trim((string) ($branding['address'] ?? ''));
$city = trim((string) ($branding['city'] ?? ''));
$country = trim((string) ($branding['country'] ?? ''));
$pdfEngine = $pdfEngine ?? 'html';
$logoMm = app(\App\Services\Agreements\AgreementPdfBranding::class)->letterheadLogoBoxMm();
@endphp
<header class="page-header">
  <div class="page-header-inner">
    <table class="page-header-table" cellpadding="0" cellspacing="0">
      <tr>
        <td class="page-header-logo">
          @include('agreements.pdf.partials.logo', [
            'branding' => $branding,
            'pdfEngine' => $pdfEngine,
            'logoHeightMm' => $logoMm['height'],
          ])
        </td>
        <td class="page-header-info">
          @if($name !== '')
          <p class="page-header-name">{{ $name }}</p>
          @endif
          @if($phone !== '')
          <p class="page-header-meta">{{ $phone }}</p>
          @endif
          @if($address !== '')
          <p class="page-header-meta">{{ $address }}{{ $city !== '' ? ', ' . $city : '' }}{{ $country !== '' ? ', ' . $country : '' }}</p>
          @endif
        </td>
      </tr>
    </table>
  </div>
  <div class="page-header-rule" style="background-color: {{ $s }};"></div>
</header>
