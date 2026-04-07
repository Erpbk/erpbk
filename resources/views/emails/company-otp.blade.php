<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verification Code') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f5; }
        .wrapper { max-width: 520px; margin: 24px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,.07); }
        .header { background: linear-gradient(135deg, #696cff 0%, #5f61e6 100%); color: #fff; padding: 28px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.5rem; font-weight: 600; }
        .body { padding: 32px 24px; }
        .otp-box { background: #f4f4f5; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
        .otp-code { font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #696cff; }
        .footer { padding: 16px 24px; text-align: center; font-size: 12px; color: #697a8d; border-top: 1px solid #e7e7e8; }
        .note { font-size: 13px; color: #697a8d; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p style="margin: 8px 0 0; opacity: .9;">{{ __('Company Registration') }}</p>
        </div>
        <div class="body">
            <p>{{ __('Hello, :name', ['name' => $companyName]) }}</p>
            <p>{{ __('Use the code below to verify your email and continue company registration.') }}</p>
            <div class="otp-box">
                <span class="otp-code">{{ $otp }}</span>
            </div>
            <p class="note">{{ __('This code expires in 15 minutes. If you did not request this, please ignore this email.') }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
        </div>
    </div>
</body>
</html>
