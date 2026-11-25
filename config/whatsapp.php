<?php

return [
    'graph_version' => env('META_WHATSAPP_GRAPH_VERSION', 'v21.0'),
    'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
    'app_secret' => env('META_WHATSAPP_APP_SECRET'),

    /** Days a downloaded/sent media file is kept on our disk before purge. */
    'media_ttl_days' => (int) env('WHATSAPP_MEDIA_TTL_DAYS', 3),

    'media' => [
        'image' => [
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_kb' => 5 * 1024,
        ],
        'audio' => [
            'mimes' => [
                'audio/aac',
                'audio/mp4',
                'audio/mpeg',
                'audio/amr',
                'audio/ogg',
                'audio/opus',
            ],
            'extensions' => ['aac', 'm4a', 'mp3', 'amr', 'ogg', 'opus'],
            'max_kb' => 16 * 1024,
        ],
        'video' => [
            'mimes' => ['video/mp4', 'video/3gpp'],
            'extensions' => ['mp4', '3gp'],
            'max_kb' => 16 * 1024,
        ],
        'document' => [
            'mimes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
            ],
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'max_kb' => 100 * 1024,
        ],
    ],
];
