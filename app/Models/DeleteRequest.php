<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeleteRequest extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /** After approval: soft-deleted and sitting in Recycle Bin. */
    public const BIN_IN_RECYCLE_BIN = 'in_recycle_bin';

    /** After approval: restored from Recycle Bin to the original module. */
    public const BIN_RESTORED = 'restored';

    /** After approval: permanently removed from Recycle Bin. */
    public const BIN_PERMANENTLY_DELETED = 'permanently_deleted';

    /** After approval: paid visa/license expense stayed in place; payment was reversed. */
    public const BIN_PAYMENT_REVERSED = 'payment_reversed';

    protected $fillable = [
        'company_id',
        'module_key',
        'module_name',
        'deletable_type',
        'deletable_id',
        'record_label',
        'record_snapshot',
        'cascaded_records',
        'requested_by',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_remarks',
        'moved_to_bin_at',
        'restored_by',
        'restored_at',
        'permanently_deleted_by',
        'permanently_deleted_at',
        'bin_outcome',
    ];

    protected $casts = [
        'record_snapshot' => 'array',
        'cascaded_records' => 'array',
        'reviewed_at' => 'datetime',
        'moved_to_bin_at' => 'datetime',
        'restored_at' => 'datetime',
        'permanently_deleted_at' => 'datetime',
    ];

    public function deletable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function restoredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function permanentlyDeletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'permanently_deleted_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isInRecycleBin(): bool
    {
        return $this->isApproved() && $this->bin_outcome === self::BIN_IN_RECYCLE_BIN;
    }

    public function wasRestoredFromBin(): bool
    {
        return $this->bin_outcome === self::BIN_RESTORED;
    }

    public function wasPermanentlyDeleted(): bool
    {
        return $this->bin_outcome === self::BIN_PERMANENTLY_DELETED;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForRequester($query, int $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeInRecycleBin($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where('bin_outcome', self::BIN_IN_RECYCLE_BIN);
    }

    public function appendCascadedRecord(string $type, $id, ?string $label = null): void
    {
        $cascaded = $this->cascaded_records ?? [];
        $cascaded[] = [
            'type' => $type,
            'id' => $id,
            'label' => $label,
        ];
        $this->cascaded_records = $cascaded;
        $this->save();
    }
}
