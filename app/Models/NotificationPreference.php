<?php

namespace App\Models;

class NotificationPreference extends BaseModel
{
    protected $fillable = [
        'company_id',
        'user_id',
        'type_key',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
