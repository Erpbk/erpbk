<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only approval / audit history for vouchers (and future approvables).
 * Updates and deletes are blocked at the model level.
 */
class VoucherApprovalEvent extends Model
{
    protected $table = 'voucher_approval_events';

    protected $fillable = [
        'company_id',
        'approvable_type',
        'approvable_id',
        'action',
        'from_status',
        'to_status',
        'user_id',
        'user_name',
        'remark',
        'ip_address',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Voucher approval events are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Voucher approval events are immutable and cannot be deleted.');
        });
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
