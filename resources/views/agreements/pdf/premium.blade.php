<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  @php
  $p = $branding['primary_color'] ?? '#2563eb';
  $s = $branding['secondary_color'] ?? '#1d4ed8';
  $pLight = $branding['primary_light'] ?? '#f0f9ff';
  $pSoft = $branding['primary_soft'] ?? '#e0f2fe';
  $pDark = $branding['primary_dark'] ?? '#0c4a6e';
  $sLight = $branding['secondary_light'] ?? '#dbeafe';
  $border = $branding['border_color'] ?? '#bae6fd';
  $onPrimary = $branding['text_on_primary'] ?? '#ffffff';
  $docDate = \Carbon\Carbon::parse($agreementDate)->format('F j, Y');
  @endphp
  <style>
    @page {
      margin: 36pt 40pt 48pt 48pt;
    }

    body {
      font-family: 'DejaVu Sans', Georgia, serif;
      font-size: 9.5pt;
      color: #334155;
      line-height: 1.22;
      margin: 0;
    }

    .side-accent {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: 8pt;

      background-color: {
          {
          $p
        }
      }

      ;
    }

    .side-accent-inner {
      position: fixed;
      left: 8pt;
      top: 0;
      bottom: 0;
      width: 3pt;

      background-color: {
          {
          $s
        }
      }

      ;
    }

    .header-card {
      border: 1px solid {
          {
          $border
        }
      }

      ;
      padding: 0;
      margin-bottom: 10pt;
      background-color: #fff;
    }

    .header-top {
      background-color: {
          {
          $p
        }
      }

      ;

      color: {
          {
          $onPrimary
        }
      }

      ;
      padding: 14pt 16pt;
    }

    .header-top table {
      width: 100%;
      border-collapse: collapse;
    }

    .header-top td {
      border: none;
      vertical-align: middle;

      color: {
          {
          $onPrimary
        }
      }

      ;
      padding: 0;
    }

    .logo-frame {
      background: #fff;
      padding: 6pt;
      display: inline-block;

      border: 2pt solid {
          {
          $sLight
        }
      }

      ;
    }

    .company-logo-img {
      max-height: 42pt;
      max-width: 80pt;
      display: block;
    }

    .company-logo-fallback {
      width: 42pt;
      height: 42pt;
      line-height: 42pt;
      text-align: center;
      font-size: 18pt;
      font-weight: bold;

      color: {
          {
          $onPrimary
        }
      }

      ;

      background-color: {
          {
          $pDark
        }
      }

      ;
    }

    .header-title {
      font-size: 17pt;
      font-weight: bold;
      margin: 0;
      letter-spacing: 0.5px;
    }

    .header-sub {
      font-size: 9pt;
      margin: 4pt 0 0;
      opacity: 0.9;
    }

    .header-bottom {
      padding: 10pt 16pt;

      background-color: {
          {
          $pLight
        }
      }

      ;

      border-top: 1px solid {
          {
          $border
        }
      }

      ;
    }

    .header-bottom table {
      width: 100%;
      font-size: 8.5pt;
      color: #475569;
    }

    .header-bottom td {
      border: none;
      padding: 2pt 0;
      vertical-align: top;
    }

    .pill {
      display: inline-block;

      background-color: {
          {
          $s
        }
      }

      ;

      color: {
          {
          $onPrimary
        }
      }

      ;
      font-size: 7.5pt;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      padding: 3pt 8pt;
      margin-bottom: 6pt;
    }

    .meta-grid {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8pt;
    }

    .meta-grid td {
      width: 50%;
      padding: 3pt 6pt;

      border: 1px solid {
          {
          $border
        }
      }

      ;

      background-color: {
          {
          $pSoft
        }
      }

      ;
      font-size: 7.5pt;
      line-height: 1.15;
      vertical-align: middle;
    }

    .meta-grid .k {
      color: {
          {
          $p
        }
      }

      ;
      font-weight: bold;
      font-size: 6.5pt;
      line-height: 1.1;
      text-transform: uppercase;
      display: block;
      margin-bottom: 1pt;
    }

    .meta-grid strong {
      font-size: 7.5pt;
      font-weight: bold;
    }

    .clause-shell {
      border-left: 4pt solid {
          {
          $p
        }
      }

      ;
      padding: 8pt 10pt 8pt 12pt;
      background-color: #fafbfc;
      margin-bottom: 6pt;
      line-height: 1.22;
    }

    .content p {
      margin: 0 0 4pt;
      line-height: 1.22;
    }

    .content h1,
    .content h2 {
      color: {
          {
          $pDark
        }
      }

      ;
      font-size: 10pt;
      margin: 0 0 4pt;
      line-height: 1.2;
    }

    .content h3 {
      color: {
          {
          $p
        }
      }

      ;
      font-size: 9.5pt;
      margin: 6pt 0 3pt;
      line-height: 1.2;
    }

    .content table {
      width: 100%;
      border-collapse: collapse;
      margin: 4pt 0;
      font-size: 7.5pt;
    }

    .content table th {
      background-color: {
          {
          $pDark
        }
      }

      ;

      color: {
          {
          $onPrimary
        }
      }

      ;
      padding: 3pt 5pt;
      line-height: 1.15;
      text-align: left;
      font-size: 7.5pt;
    }

    .content table td,
    .content table th {
      padding: 3pt 5pt !important;
      font-size: 7.5pt !important;
      line-height: 1.15 !important;
    }

    .content table td {
      border-bottom: 1px solid {
          {
          $border
        }
      }

      ;
      background-color: #fff;
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

    .content table tr:nth-child(even) td {
      background-color: {
          {
          $pLight
        }
      }

      ;
    }

    .signatures-wrap {
      margin-top: 18pt;
      page-break-inside: avoid;
    }

    .signatures-wrap table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 10pt 0;
    }

    .signatures-wrap td {
      width: 50%;

      border: 2pt solid {
          {
          $p
        }
      }

      ;
      padding: 14pt;
      vertical-align: top;
      background-color: #fff;
    }

    .signatures-wrap .head {
      background-color: {
          {
          $p
        }
      }

      ;

      color: {
          {
          $onPrimary
        }
      }

      ;
      margin: -14pt -14pt 10pt -14pt;
      padding: 6pt 10pt;
      font-size: 8pt;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.6px;
    }

    .sign-line {
      border-bottom: 1px solid #94a3b8;
      height: 26pt;
      margin: 8pt 0;
    }

    .page-foot {
      position: fixed;
      bottom: 0;
      left: 48pt;
      right: 40pt;
      font-size: 7.5pt;
      color: #94a3b8;
      text-align: center;
      padding-top: 6pt;

      border-top: 1px solid {
          {
          $border
        }
      }

      ;
    }
  </style>
</head>

<body>
  <div class="side-accent"></div>
  <div class="side-accent-inner"></div>

  <div class="pill">Legal Document</div>

  <div class="header-card">
    <div class="header-top">
      <table>
        <tr>
          <td style="width: 72%;">
            <div class="header-title">{{ $template->template_name ?? 'Agreement' }}</div>
            <div class="header-sub">{{ $branding['name'] ?? '' }} &bull; {{ $docDate }}</div>
          </td>
          <td style="text-align: right;">
            <div class="logo-frame">
              @include('agreements.pdf.partials.logo')
            </div>
          </td>
        </tr>
      </table>
    </div>
    <div class="header-bottom">
      <table>
        <tr>
          <td width="55%">
            @if(!empty($branding['address'])){{ $branding['address'] }}<br>@endif
            @if(!empty($branding['location_line'])){{ $branding['location_line'] }}@endif
          </td>
          <td width="45%" style="text-align: right;">
            @if(!empty($branding['phone']))<strong>Phone:</strong> {{ $branding['phone'] }}<br>@endif
            @if(!empty($branding['email']))<strong>Email:</strong> {{ $branding['email'] }}@endif
          </td>
        </tr>
      </table>
    </div>
  </div>

  <table class="meta-grid">
    <tr>
      <td>
        <span class="k">Party (Rider)</span>
        <strong>{{ $rider->name ?? '—' }}</strong>
      </td>
      <td>
        <span class="k">Rider ID</span>
        {{ $rider->rider_id ?? '—' }}
      </td>
    </tr>
    <tr>
      <td>
        <span class="k">Email / Contact</span>
        {{ $rider->email ?? '—' }}
      </td>
      <td>
        <span class="k">Agreement Date</span>
        {{ $docDate }}
      </td>
    </tr>
  </table>

  <div class="clause-shell content">
    {!! $body !!}
  </div>

  <div class="signatures-wrap">
    <table>
      <tr>
        <td>
          <div class="head">Company Authorization</div>
          <div class="sign-line"></div>
          <div style="font-size:9pt;">For and on behalf of</div>
          <div style="font-size:10pt;font-weight:bold;color:{{ $pDark }};">{{ $branding['name'] ?? '' }}</div>
          <div style="margin-top:12pt;font-size:8pt;color:#64748b;">Signature &amp; stamp</div>
        </td>
        <td>
          <div class="head">Rider Acknowledgement</div>
          <div class="sign-line"></div>
          <div style="font-size:10pt;font-weight:bold;">{{ $rider->name ?? '' }}</div>
          <div style="font-size:8.5pt;color:#64748b;">ID: {{ $rider->rider_id ?? '—' }}</div>
          <div style="margin-top:12pt;font-size:8pt;color:#64748b;">I have read and agree to the terms above.</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="page-foot">{{ $branding['name'] ?? '' }} &mdash; Confidential</div>
</body>

</html>