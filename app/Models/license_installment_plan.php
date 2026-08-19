<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use Illuminate\Support\Collection;

class license_installment_plan extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const VOUCHER_TYPE = 'LE';
    public const VOUCHER_REASON = 'license_installment';
    public const REFERENCE_TYPE = 'LI';

    protected $table = 'license_installment_plans';

    protected $fillable = [
        'date',
        'branch_id',
        'billing_month',
        'rider_id',
        'amount',
        'total_amount',
        'reference_number',
        'narration',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';

    /**
     * Historical name: rider_id may be expense_accounts.id, riders.id, or accounts.id.
     */
    public function rider()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(ExpenseAccount::class, 'rider_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function vouchers()
    {
        return $this->hasMany(Vouchers::class, 'ref_id', 'id')
            ->where('voucher_type', self::VOUCHER_TYPE)
            ->where('reason', self::VOUCHER_REASON);
    }

    public function installmentTransactions()
    {
        return $this->hasMany(Transactions::class, 'reference_id', 'id')
            ->where('reference_type', self::REFERENCE_TYPE);
    }

    /**
     * @return Collection<int, string>
     */
    protected function transactionNarrationStrings(): Collection
    {
        $rows = $this->relationLoaded('installmentTransactions')
            ? $this->installmentTransactions
            : $this->installmentTransactions()->get();

        if ($rows->isNotEmpty()) {
            return $rows->pluck('narration')
                ->filter(static fn ($n) => $n !== null && $n !== '')
                ->unique()
                ->values();
        }

        $fallback = $this->attributes['narration'] ?? null;
        if ($fallback !== null && $fallback !== '') {
            return collect([(string) $fallback]);
        }

        return collect();
    }

    public function getTransactionNarrationAttribute(): ?string
    {
        $strings = $this->transactionNarrationStrings();

        return $strings->isEmpty() ? null : $strings->implode(' | ');
    }

    public function getTransactionNarrationPlainAttribute(): string
    {
        $strings = $this->transactionNarrationStrings();
        if ($strings->isEmpty()) {
            return '';
        }

        return $strings
            ->map(static function ($html) {
                return html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            })
            ->unique()
            ->implode(' | ');
    }

    public function getVoucherIdsAttribute()
    {
        if (!$this->relationLoaded('vouchers')) {
            $this->loadMissing('vouchers');
        }

        if ($this->vouchers->isEmpty()) {
            return '';
        }

        return $this->vouchers->map(function ($voucher) {
            $prefix = $voucher->voucher_type ?: 'V';
            $number = str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
            return "{$prefix}-{$number}";
        })->implode(', ');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForRider($query, $riderId)
    {
        return $query->where('rider_id', $riderId);
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            self::STATUS_PAID => '<span class="badge bg-success">Paid</span>',
            self::STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
}
