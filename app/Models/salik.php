<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class salik extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes, BranchScope;
    
    protected $table = 'saliks';
    
    protected $fillable = [
        'branch_id',
        'inv_id',
        'transaction_id',
        'trip_date',
        'trip_time',
        'billing_month',
        'transaction_post_date',
        'toll_gate',
        'direction',
        'tag_number',
        'plate',
        'amount',
        'salik_vat',
        'salik_vat_amount',
        'trans_date',
        'trans_code',
        'rider_id',
        'rental_company_id',
        'bike_id',
        'admin_charges',
        'admin_vat',
        'admin_vat_amount',
        'vat',
        'salik_account_id',
        'attachments',
        'total_amount',
        'details',
        'status',
        'payment_voucher_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'amount' => 'decimal:2',
        'admin_charges' => 'decimal:2',
        'salik_vat' => 'decimal:2',
        'salik_vat_amount' => 'decimal:2',
        'admin_vat' => 'decimal:2',
        'admin_vat_amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->inv_id) || !$model->billing_month) {
                return;
            }

            $billingMonth = self::normalizeBillingMonth($model->billing_month);

            if ($model->rider_id) {
                $model->inv_id = self::getOrCreateInvId($model->rider_id, $billingMonth);
            } elseif ($model->rental_company_id) {
                $model->inv_id = self::getOrCreateInvIdForRentalCompany($model->rental_company_id, $billingMonth);
            }
        });
    }

    public static function normalizeBillingMonth($billingMonth): ?string
    {
        if (!$billingMonth) {
            return null;
        }

        try {
            return Carbon::parse($billingMonth)->format('Y-m-01');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getOrCreateInvId($riderId, $billingMonth): string
    {
        $billingMonth = self::normalizeBillingMonth($billingMonth);

        $existing = self::where('rider_id', $riderId)
            ->whereNotNull('inv_id')
            ->where(function ($query) use ($billingMonth) {
                self::applyBillingMonthFilter($query, $billingMonth);
            })
            ->first();

        if ($existing && $existing->inv_id) {
            return $existing->inv_id;
        }

        return self::generateNewInvId();
    }

    public static function getOrCreateInvIdForRentalCompany($rentalCompanyId, $billingMonth): string
    {
        $billingMonth = self::normalizeBillingMonth($billingMonth);

        $existing = self::where('rental_company_id', $rentalCompanyId)
            ->whereNull('rider_id')
            ->whereNotNull('inv_id')
            ->where(function ($query) use ($billingMonth) {
                self::applyBillingMonthFilter($query, $billingMonth);
            })
            ->first();

        if ($existing && $existing->inv_id) {
            return $existing->inv_id;
        }

        return self::generateNewInvId();
    }

    public static function generateNewInvId(): string
    {
        $result = self::whereNotNull('inv_id')
            ->whereRaw('inv_id REGEXP "^SLK-[0-9]+$"')
            ->selectRaw('MAX(CAST(REPLACE(inv_id, "SLK-", "") AS UNSIGNED)) as max_number')
            ->first();

        $maxNumber = $result->max_number ?? 0;
        $sequence = str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);

        return "SLK-{$sequence}";
    }

    public static function applyBillingMonthFilter($query, ?string $billingMonth)
    {
        if (!$billingMonth) {
            return $query;
        }

        $month = Carbon::parse($billingMonth);

        return $query->where(function ($q) use ($billingMonth, $month) {
            $q->where('billing_month', $billingMonth)
                ->orWhere('billing_month', 'like', $month->format('Y-m') . '%')
                ->orWhere('billing_month', 'like', $month->format('M') . '-' . $month->format('y') . '%');
        });
    }

    public function getMonthlySummary()
    {
        if (!$this->inv_id) {
            return null;
        }

        $transactions = self::where('inv_id', $this->inv_id)
            ->with(['rider', 'rentalCompany'])
            ->get();

        $chargeeName = $this->rider->name
            ?? $this->rentalCompany->name
            ?? 'N/A';

        return (object) [
            'inv_id' => $this->inv_id,
            'rider_id' => $this->rider_id,
            'rental_company_id' => $this->rental_company_id,
            'rider_name' => $this->rider->name ?? null,
            'company_name' => $this->rentalCompany->name ?? null,
            'chargee_name' => $chargeeName,
            'billing_month' => self::normalizeBillingMonth($this->billing_month),
            'transaction_count' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'total_admin_charges' => $transactions->sum('admin_charges'),
            'total_vat' => $transactions->sum('vat'),
            'total_grand' => $transactions->sum('total_amount'),
            'transactions' => $transactions,
        ];
    }

    public static function normalizePaymentStatus(?string $status, bool $isPaid = false): string
    {
        if ($isPaid || strtolower((string) $status) === 'paid') {
            return 'paid';
        }

        return 'unpaid';
    }

    public function isPaid(): bool
    {
        return self::normalizePaymentStatus($this->status, !empty($this->payment_voucher_id)) === 'paid';
    }

    public function scopeUnpaid($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['paid']);
            })->where(function ($inner) {
                $inner->whereNull('payment_voucher_id')
                    ->orWhere('payment_voucher_id', 0);
            });
        });
    }

    public function scopePaid($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('LOWER(status) = ?', ['paid'])
                ->orWhere(function ($inner) {
                    $inner->whereNotNull('payment_voucher_id')
                        ->where('payment_voucher_id', '!=', 0);
                });
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function bike()
    {
        return $this->belongsTo(Bikes::class, 'bike_id');
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function rentalCompany()
    {
        return $this->belongsTo(BikeRentCompany::class, 'rental_company_id');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(Vouchers::class, 'payment_voucher_id');
    }
}
