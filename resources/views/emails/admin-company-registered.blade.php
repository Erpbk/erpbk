<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('New company registered') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f5; }
        .wrapper { max-width: 560px; margin: 24px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,.07); }
        .header { background: #696cff; color: #fff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 1.25rem; }
        .body { padding: 24px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 8px 0; border-bottom: 1px solid #eee; }
        table.info td:first-child { font-weight: 600; color: #697a8d; width: 140px; }
        .footer { padding: 16px 24px; font-size: 12px; color: #697a8d; border-top: 1px solid #e7e7e8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ __('New company registered') }}</h1>
        </div>
        <div class="body">
            <p>{{ __('A new company has registered and is pending approval.') }}</p>
            <table class="info">
                <tr><td>{{ __('Company name') }}</td><td>{{ $company->name }}</td></tr>
                <tr><td>{{ __('Email') }}</td><td>{{ $company->email }}</td></tr>
                <tr><td>{{ __('Country') }}</td><td>{{ $company->country }}</td></tr>
                <tr><td>{{ __('Phone') }}</td><td>{{ $company->phone }}</td></tr>
                <tr><td>{{ __('Registered at') }}</td><td>{{ $company->created_at->format('M j, Y H:i') }}</td></tr>
            </table>
            <p style="margin-top: 20px;">{{ __('Please review and approve or reject the company from the admin panel.') }}</p>
        </div>
        <div class="footer">
            {{ config('app.name') }} Admin
        </div>
    </div>
</body>
</html>
