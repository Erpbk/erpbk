@php
$brand = $emailBranding ?? [];
$companyName = $brand['name'] ?? config('app.name');
$primaryColor = $brand['primary_color'] ?? '#2563eb';
$secondaryColor = $brand['secondary_color'] ?? '#1e3a8a';
$logoUrl = $brand['logo_url'] ?? null;
$footerLines = $brand['footer_lines'] ?? [];
$contactEmail = $brand['email'] ?? '';
$emailHeading = $email_heading ?? null;
$riderName = $rider_name ?? null;
$riderIdLabel = $rider_id ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>{{ $companyName }}</title>
</head>

<body style="margin:0;padding:0;background-color:#eef2f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;width:100% !important;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;width:100%;margin:0;padding:0;">
    <tr>
      <td align="center" style="padding:16px 8px;width:100%;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:100%;margin:0 auto;">

          <tr>
            <td style="height:5px;background-color:{{ $primaryColor }};border-radius:10px 10px 0 0;font-size:0;line-height:0;width:100%;">&nbsp;</td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;padding:0;text-align:center;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;width:100%;">
              @if($logoUrl)
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                  <td style="background-color:#f8fafc;border-bottom:1px solid #e2e8f0;padding:20px 16px;width:100%;">
                    <img
                      src="{{ $logoUrl }}"
                      alt="{{ $companyName }}"
                      width="560"
                      style="display:block;width:100%;max-width:110px;height:auto;max-height:100px;margin:0 auto;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;" />
                  </td>
                </tr>
              </table>
              @endif

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                  <td style="padding:20px 20px 16px;width:100%;">
                    @if(!empty($emailHeading))
                    <p style="margin:0 0 8px;font-size:18px;font-weight:700;color:{{ $secondaryColor }};line-height:1.35;text-align:center;">
                      {{ $emailHeading }}
                    </p>
                    @if($riderName)
                    <p style="margin:0 0 14px;font-size:14px;font-weight:500;color:#475569;line-height:1.4;text-align:center;">
                      {{ $riderName }}@if($riderIdLabel) <span style="color:#64748b;">(Rider ID: {{ $riderIdLabel }})</span>@endif
                    </p>
                    @endif
                    @endif
                    <p style="margin:0;font-size:20px;font-weight:700;color:{{ $secondaryColor }};line-height:1.3;text-align:center;">
                      {{ $companyName }}
                    </p>
                    <p style="margin:6px 0 0;font-size:13px;color:#64748b;line-height:1.4;text-align:center;">
                      Official communication
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;padding:0 16px 20px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;width:100%;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                <tr>
                  <td style="background-color:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid {{ $primaryColor }};border-radius:8px;padding:20px 18px;width:100%;">
                    <div style="font-size:15px;line-height:1.65;color:#334155;width:100%;word-wrap:break-word;">
                      @yield('message')
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background-color:#f1f5f9;padding:20px 16px;text-align:center;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 10px 10px;width:100%;">
              <p style="margin:0 0 8px;font-size:14px;font-weight:600;color:{{ $secondaryColor }};">
                {{ $companyName }}
              </p>
              @foreach($footerLines as $line)
              <p style="margin:0 0 4px;font-size:12px;color:#64748b;line-height:1.5;">{{ $line }}</p>
              @endforeach
              @if($contactEmail !== '')
              <p style="margin:12px 0 0;font-size:12px;line-height:1.5;">
                <a href="mailto:{{ $contactEmail }}" style="color:{{ $primaryColor }};text-decoration:none;font-weight:500;">{{ $contactEmail }}</a>
              </p>
              @endif
              <p style="margin:14px 0 0;font-size:11px;color:#94a3b8;line-height:1.4;">
                This message was sent on behalf of {{ $companyName }}.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>

</html>