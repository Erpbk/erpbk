<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRule extends BaseModel
{
    protected $fillable = [
        'company_id',
        'type_key',
        'enabled',
        'config',
        'recipient_config',
        'channels',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
        'recipient_config' => 'array',
        'channels' => 'array',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'rule_id');
    }
}
