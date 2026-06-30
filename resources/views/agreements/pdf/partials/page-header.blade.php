@php
$s = $branding['secondary_color'] ?? '#2563eb';
$email = $branding['email'] ?? '';
$phone = $branding['phone'] ?? '';
$website = $branding['website_display'] ?? '';
$address = $branding['address'] ?? '';
$locationLine = $branding['location_line'] ?? '';
$addressLine = $locationLine !== '' ? $locationLine : $address;
@endphp
<header class="page-header">
  <div class="page-header-inner">
    <table class="page-header-table" cellpadding="0" cellspacing="0">
      <tr>
        <td class="page-header-logo">
          @include('agreements.pdf.partials.logo')
        </td>
        <td class="page-header-info">
          @if($email !== '')
          <p class="page-header-meta">{{ $email }}</p>
          @endif
          @if($website !== '')
          <p class="page-header-meta">{{ $website }}</p>
          @endif
          @if($phone !== '')
          <p class="page-header-meta">{{ $phone }}</p>
          @endif
          @if($addressLine !== '')
          <p class="page-header-meta">{{ $addressLine }}</p>
          @endif
        </td>
      </tr>
    </table>
  </div>
  <div class="page-header-rule" style="background-color: {{ $s }};"></div>
</header>
