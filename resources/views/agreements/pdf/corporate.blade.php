<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  @php
  $p = $branding['primary_color'] ?? '#1e3a8a';
  $s = $branding['secondary_color'] ?? '#2563eb';
  $pLight = $branding['primary_light'] ?? '#eef2ff';
  $pSoft = $branding['primary_soft'] ?? '#e0e7ff';
  $pDark = $branding['primary_dark'] ?? '#0f172a';
  $border = $branding['border_color'] ?? '#c7d2fe';
  $onPrimary = $branding['text_on_primary'] ?? '#ffffff';
  $docDate = \Carbon\Carbon::parse($agreementDate)->format('d M Y');
  $docRef = 'AGR-' . str_pad((string) ($template->id ?? 0), 5, '0', STR_PAD_LEFT) . '-' . date('Y');
  @endphp
  <style>
    @page {
      margin: 0;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'DejaVu Sans', Calibri, sans-serif;
      font-size: 9.5pt;
      color: #1e293b;
      line-height: 1.22;
      margin: 0;
      padding: 0;
    }

    .page-wrap {
      padding: 0 42pt 48pt 42pt;
    }

    .top-band {
      background-color: {{ $p }};
      color: {{ $onPrimary }};
      padding: 16pt 42pt 14pt 42pt;
    }

    .top-band table {
      width: 100%;
      border-collapse: collapse;
    }

    .top-band td {
      vertical-align: middle;
      border: none;
      padding: 0;
      color: {{ $onPrimary }};
    }

    .logo-cell {
      width: 95pt;
    }

    .company-logo-img {
      max-height: 48pt;
      max-width: 88pt;
      display: block;
      background: #fff;
      padding: 4pt;
    }

    .company-logo-fallback {
      width: 48pt;
      height: 48pt;
      line-height: 48pt;
      text-align: center;
      font-size: 20pt;
      font-weight: bold;
      border-radius: 4pt;
    }

    .brand-name {
      font-size: 15pt;
      font-weight: bold;
      margin: 0 0 3pt;
      letter-spacing: 0.3px;
    }

    .brand-meta {
      font-size: 8.5pt;
      opacity: 0.92;
      line-height: 1.35;
      margin: 0;
    }

    .doc-badge {
      background-color: {{ $pDark }};
      color: {{ $onPrimary }};
      text-align: right;
      padding: 8pt 10pt;
      font-size: 8.5pt;
      line-height: 1.4;
    }

    .doc-badge strong {
      display: block;
      font-size: 9.5pt;
      margin-bottom: 2pt;
    }

    .accent-rule {
      height: 4pt;
      background-color: {{ $s }};
      margin: 0;
    }

    .title-block {
      text-align: center;
      padding: 10pt 0 8pt;
      border-bottom: 1px solid {{ $border }};
      margin-bottom: 8pt;
    }

    .title-block h1 {
      font-size: 12.5pt;
      font-weight: bold;
      color: {{ $pDark }};
      margin: 0 0 2pt;
      line-height: 1.2;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .title-block .sub {
      font-size: 8pt;
      color: #64748b;
      line-height: 1.2;
    }

    .rider-strip {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8pt;
      background-color: {{ $pLight }};
      border: 1px solid {{ $border }};
    }

    .rider-strip td {
      padding: 3pt 6pt;
      font-size: 7.5pt;
      line-height: 1.15;
      border: none;
      vertical-align: middle;
    }

    .rider-strip .label {
      color: {{ $p }};
      font-weight: bold;
      font-size: 6.5pt;
      line-height: 1.1;
      text-transform: uppercase;
      margin-bottom: 1pt;
    }

    .rider-strip .value {
      color: #0f172a;
      font-size: 7.5pt;
      line-height: 1.15;
    }

    .content {
      font-size: 9.5pt;
      line-height: 1.22;
    }

    .content p {
      margin: 0 0 4pt;
      line-height: 1.22;
    }

    .content h2,
    .content h3 {
      color: {{ $p }};
      font-size: 10pt;
      margin: 8pt 0 4pt;
      line-height: 1.2;
    }

    .content table {
      width: 100%;
      border-collapse: collapse;
      margin: 4pt 0;
    }

    .content table th {
      background-color: {{ $p }};
      color: {{ $onPrimary }};
      padding: 3pt 5pt;
      font-size: 7.5pt;
      line-height: 1.15;
      text-align: left;
      border: 1px solid {{ $pDark }};
    }

    .content table td,
    .content table th {
      padding: 3pt 5pt !important;
      font-size: 7.5pt !important;
      line-height: 1.15 !important;
    }

    .content table td {
      border: 1px solid {{ $border }};
    }

    .content table tr:nth-child(even) td {
      background-color: {{ $pLight }};
    }

    .content ul,
    .content ol {
      margin: 3pt 0 4pt 14pt;
      padding: 0;
      line-height: 1.2;
    }

    .content li {
      margin-bottom: 1pt;
    }

    .signatures {
      margin-top: 18pt;
      page-break-inside: avoid;
    }

    .signatures table {
      width: 100%;
      border-collapse: collapse;
    }

    .signatures td {
      width: 48%;
      vertical-align: top;
      padding: 8pt;
      border: 1px solid {{ $border }};
      background-color: {{ $pSoft }};
      font-size: 8.5pt;
      line-height: 1.2;
    }

    .signatures td+td {
      border-left: 3pt solid {{ $p }};
    }

    .sign-title {
      font-size: 8pt;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: {{ $p }};
      font-weight: bold;
      margin-bottom: 8pt;
    }

    .sign-line {
      border-bottom: 1px solid #64748b;
      height: 22pt;
      margin: 6pt 0 4pt;
    }

    .footer-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      font-size: 7.5pt;
      color: #64748b;
      padding: 8pt 42pt;
      border-top: 2pt solid {{ $p }};
      background-color: {{ $pLight }};
    }

    .footer-bar table {
      width: 100%;
    }

    .footer-bar td {
      border: none;
      padding: 0;
      vertical-align: middle;
    }
  </style>
</head>

<body>
  <div class="top-band">
    <table>
      <tr>
        <td class="logo-cell">
          @include('agreements.pdf.partials.logo')
        </td>
        <td style="padding-left: 12pt;">
          <p class="brand-name">{{ $branding['name'] ?? '' }}</p>
          @if(!empty($branding['address']))<p class="brand-meta">{{ $branding['address'] }}</p>@endif
          @if(!empty($branding['location_line']))<p class="brand-meta">{{ $branding['location_line'] }}</p>@endif
          @if(!empty($branding['phone']) || !empty($branding['email']))
          <p class="brand-meta">
            @if(!empty($branding['phone']))Tel: {{ $branding['phone'] }}@endif
            @if(!empty($branding['phone']) && !empty($branding['email'])) &nbsp;|&nbsp; @endif
            @if(!empty($branding['email'])){{ $branding['email'] }}@endif
          </p>
          @endif
        </td>
        <td style="width: 108pt; vertical-align: top;">
          <div class="doc-badge">
            <strong>OFFICIAL AGREEMENT</strong>
            Ref: {{ $docRef }}<br>
            Date: {{ $docDate }}
          </div>
        </td>
      </tr>
    </table>
  </div>
  <div class="accent-rule"></div>

  <div class="page-wrap">
    <div class="title-block">
      <h1>{{ $template->template_name ?? 'Agreement Document' }}</h1>
      <div class="sub">{{ optional($category)->name ?? 'Contract' }} &mdash; {{ $branding['name'] ?? '' }}</div>
    </div>


    <div class="content">
      {!! $body !!}
    </div>

    <div class="signatures">
      <table>
        <tr>
          <td>
            <div class="sign-title">Company Signature</div>
            <div class="sign-line"></div>
            <div>Authorized signatory</div>
            <div style="margin-top:6pt;font-size:9pt;"><strong>{{ $branding['name'] ?? '' }}</strong></div>
            <div style="margin-top:10pt;font-size:8pt;">Date: _______________________</div>
          </td>
          <td>
            <div class="sign-title">Rider Signature</div>
            <div class="sign-line"></div>
            <div style="font-size:9.5pt;"><strong>{{ $rider->name ?? '' }}</strong></div>
            <div style="font-size:8.5pt;color:#475569;">ID: {{ $rider->rider_id ?? '—' }}</div>
            <div style="margin-top:10pt;font-size:8pt;">Date: _______________________</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="footer-bar">
    <table>
      <tr>
        <td>{{ $branding['name'] ?? '' }} &mdash; Confidential. Unauthorized reproduction prohibited.</td>
        <td style="text-align:right;">{{ $docRef }}</td>
      </tr>
    </table>
  </div>
</body>

</html>