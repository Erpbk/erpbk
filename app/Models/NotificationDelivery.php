<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends BaseModel
{
    protected $fillable = [
        'company_id',
        'user_notification_id',
        'channel',
        'to_email',
        'status',
        'provider_response',
        'attempts',
        'last_attempt_at',
        'sent_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public function notification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class, 'user_notification_id');
    }
}
