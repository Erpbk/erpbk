<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppUnknownInboxSetting extends BaseModel
{
    protected $table = 'whatsapp_unknown_inbox_settings';

    protected $fillable = [
        'company_id',
        'whatsapp_connection_id',
        'user_id',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConnection::class, 'whatsapp_connection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
