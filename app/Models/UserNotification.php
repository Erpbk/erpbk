<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserNotification extends BaseModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'type',
        'severity',
        'title',
        'body',
        'data',
        'source_type',
        'source_id',
        'action_url',
        'rule_id',
        'dedupe_key',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'rule_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'user_notification_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')->whereNull('dismissed_at');
    }

    public function scopeUndismissed($query)
    {
        return $query->whereNull('dismissed_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function dismiss(): void
    {
        if ($this->dismissed_at === null) {
            $this->forceFill([
                'dismissed_at' => now(),
                'read_at' => $this->read_at ?? now(),
            ])->save();
        }
    }

    public function isUnread(): bool
    {
        return $this->read_at === null && $this->dismissed_at === null;
    }
}
