@php
$s = $branding['secondary_color'] ?? '#2563eb';
$name = trim((string) ($branding['name'] ?? ''));
$email = trim((string) ($branding['email'] ?? ''));
$phone = trim((string) ($branding['phone'] ?? ''));
$address = trim((string) ($branding['address'] ?? ''));
$locationLine = trim((string) ($branding['location_line'] ?? ''));
$city = trim((string) ($branding['city'] ?? ''));
$country = trim((string) ($branding['country'] ?? ''));
@endphp
<header class="page-header">
  <div class="page-header-inner">
    <table class="page-header-table" cellpadding="0" cellspacing="0">
      <tr>
        <td class="page-header-logo">
          @include('agreements.pdf.partials.logo')
        </td>
        <td class="page-header-info">
          @if($name !== '')
          <p class="page-header-meta">{{ $name }}</p>
          @endif
          @if($email !== '')
          <p class="page-header-meta">{{ $email }}</p>
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