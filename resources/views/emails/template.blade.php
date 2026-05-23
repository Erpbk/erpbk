@php
dd($emailBranding);

$brand = $emailBranding ?? [];

$companyName = $brand['name'] ?? config('app.name');
$primaryColor = $brand['primary_color'] ?? '#2563eb';
$secondaryColor = $brand['secondary_color'] ?? '#1e3a8a';
$logoUrl = $brand['logo_url'] ?? null;
$footerLines = $brand['footer_lines'] ?? [];
$contactEmail = $brand['email'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>{{ $companyName }}</title>
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->
</head>

<body style="margin:0;padding:0;background-color:#eef2f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;min-height:100%;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

          {{-- Top accent bar --}}
          <tr>
            <td style="height:5px;background-color:{{ $primaryColor }};border-radius:12px 12px 0 0;font-size:0;line-height:0;">&nbsp;</td>
          </tr>

          {{-- Header: logo + company name --}}
          <tr>
            <td style="background-color:#ffffff;padding:28px 32px 24px;text-align:center;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
              @if($logoUrl)
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 16px;">
                <tr>
                  <td style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 20px;">
                    <img
                      src="{{ $logoUrl }}"
                      alt="{{ $companyName }}"
                      width="180"
                      style="display:block;max-width:180px;max-height:72px;width:auto;height:auto;margin:0 auto;border:0;outline:none;text-decoration:none;" />
                  </td>
                </tr>
              </table>
              @endif
              <p style="margin:0;font-size:22px;font-weight:700;color:{{ $secondaryColor }};letter-spacing:-0.02em;line-height:1.3;">
                {{ $companyName }}
              </p>
              <p style="margin:8px 0 0;font-size:13px;color:#64748b;line-height:1.4;">
                Official communication
              </p>
            </td>
          </tr>

          {{-- Message body --}}
          <tr>
            <td style="background-color:#ffffff;padding:0 32px 32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="background-color:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid {{ $primaryColor }};border-radius:8px;padding:24px 28px;">
                    <div style="font-size:15px;line-height:1.65;color:#334155;">
                      @yield('message')
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background-color:#f1f5f9;padding:24px 32px;text-align:center;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;">
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
              <table role="presentation" width="80" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:16px auto 0;">
                <tr>
                  <td style="height:1px;background-color:#cbd5e1;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
              </table>
              <p style="margin:12px 0 0;font-size:11px;color:#94a3b8;line-height:1.4;">
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