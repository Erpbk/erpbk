<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WhatsAppMessage extends BaseModel
{
    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RECEIVED = 'received';

    public const SOURCE_CHAT_TRANSFER = 'chat_transfer';

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'company_id',
        'whatsapp_connection_id',
        'phone_number_id',
        'direction',
        'peer_phone',
        'wa_message_id',
        'message_type',
        'body',
        'template_name',
        'template_language',
        'components',
        'status',
        'status_at',
        'notification_delivery_id',
        'source',
        'is_system',
        'trigger_key',
        'participant_type',
        'participant_id',
        'participant_conflict',
        'user_id',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'components' => 'array',
        'payload' => 'array',
        'participant_conflict' => 'array',
        'is_system' => 'boolean',
        'status_at' => 'datetime',
        'occurred_at' => 'datetime',
    ];

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConnection::class, 'whatsapp_connection_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(NotificationDelivery::class, 'notification_delivery_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @deprecated Use user() */
    public function sentByUser(): BelongsTo
    {
        return $this->user();
    }
}
