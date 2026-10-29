<?php

namespace App\Traits;

use App\Models\User;
use App\Models\VoucherApprovalEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasApprovalWorkflow
{
    public static function bootHasApprovalWorkflow(): void
    {
        static::creating(function ($model) {
            if (! config('voucher_approval.enabled', true)) {
                return;
            }

            if (! Schema::hasColumn($model->getTable(), 'approval_status')) {
                return;
            }

            if (empty($model->approval_status)) {
                $model->approval_status = config('voucher_approval.statuses.pending_approval', 'pending_approval');
            }

            if (empty($model->submitted_at) && $model->approval_status === 'pending_approval') {
                $model->submitted_at = now();
            }
        });

        static::created(function ($model) {
            if (! config('voucher_approval.enabled', true)) {
                return;
            }

            if (! Schema::hasTable('voucher_approval_events')) {
                return;
            }

            if (($model->approval_status ?? null) !== 'pending_approval') {
                return;
            }

            $user = Auth::user();
            $model->approvalEvents()->create([
                'company_id' => $model->company_id ?? null,
                'action' => 'submitted',
                'from_status' => null,
                'to_status' => 'pending_approval',
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'remark' => 'Voucher submitted for approval',
                'ip_address' => request()?->ip(),
                'details' => [
                    'voucher_type' => $model->voucher_type ?? null,
                    'amount' => $model->amount ?? null,
                    'trans_code' => $model->trans_code ?? null,
                ],
            ]);
        });
    }

    public function initializeHasApprovalWorkflow(): void
    {
        $this->fillable = array_values(array_unique(array_merge($this->fillable ?? [], [
            'approval_status',
            'approved_by',
            'approved_at',
            'rejected_by',
            'rejected_at',
            'rejection_reason',
            'submitted_at',
            'approval_locked_at',
            'approval_posting_payload',
        ])));

        $this->casts = array_merge($this->casts ?? [], [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approval_locked_at' => 'datetime',
            'approval_posting_payload' => 'array',
        ]);
    }

    public function approvalEvents(): MorphMany
    {
        return $this->morphMany(VoucherApprovalEvent::class, 'approvable')->orderBy('id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPendingApproval(): bool
    {
        return ($this->approval_status ?? null) === 'pending_approval';
    }

    public function isApprovalApproved(): bool
    {
        return ($this->approval_status ?? null) === 'approved';
    }

    public function isApprovalRejected(): bool
    {
        return ($this->approval_status ?? null) === 'rejected';
    }

    public function isApprovalLocked(): bool
    {
        return $this->isApprovalApproved() && $this->approval_locked_at !== null;
    }

    /**
     * Active list: exclude rejected (pending + approved remain visible to creators).
     */
    public function scopeActiveApproval(Builder $query): Builder
    {
        if (! Schema::hasColumn($this->getTable(), 'approval_status')) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            $q->whereNull('approval_status')
                ->orWhere('approval_status', '!=', 'rejected');
        });
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending_approval');
    }

    public function scopeApprovalRejected(Builder $query): Builder
    {
        return $query->where('approval_status', 'rejected');
    }

    public function scopeApprovalApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    /**
     * Ledger-visible vouchers only.
     */
    public function scopePostedForLedger(Builder $query): Builder
    {
        if (! Schema::hasColumn($this->getTable(), 'approval_status')) {
            return $query;
        }

        return $query->where('approval_status', 'approved');
    }
}
