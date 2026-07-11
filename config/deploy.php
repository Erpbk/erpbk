<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy webhook
    |--------------------------------------------------------------------------
    |
    | After uploading files (FTP/SFTP/rsync), trigger deployment by POSTing to
    | /api/deploy/webhook with header X-Deploy-Secret or ?secret=...
    |
    */

    'webhook_enabled' => env('DEPLOY_WEBHOOK_ENABLED', false),

    'webhook_secret' => env('DEPLOY_WEBHOOK_SECRET'),

    /*
    | Comma-separated IPs allowed to call the webhook (empty = any IP).
    | Example: DEPLOY_WEBHOOK_IPS=203.0.113.10,198.51.100.0
    */
    'webhook_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DEPLOY_WEBHOOK_IPS', ''))
    ))),

    /*
    | When true, creating storage/framework/deploy.pending triggers deploy via
    | the scheduler (every minute). Useful when HTTP webhooks are unavailable.
    */
    'marker_enabled' => env('DEPLOY_MARKER_ENABLED', true),

];
