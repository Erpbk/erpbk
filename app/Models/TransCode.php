<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class TransCode extends BaseModel
{
    protected $table = 'trans_codes';

    protected $fillable = [
        'company_id',
        'last_trans_code',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Allocate the next company-wide trans_code.
     * Branch scope is never applied: one sequence per company.
     */
    public static function allocateNext(int $companyId): int
    {
        return (int) DB::transaction(function () use ($companyId) {
            $row = static::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $row->last_trans_code = (int) $row->last_trans_code + 1;
                $row->save();

                return (int) $row->last_trans_code;
            }

            $next = static::currentMaxForCompany($companyId) + 1;

            try {
                static::query()->withoutGlobalScopes()->create([
                    'company_id' => $companyId,
                    'last_trans_code' => $next,
                ]);

                return $next;
            } catch (UniqueConstraintViolationException $e) {
                $row = static::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $row->last_trans_code = (int) $row->last_trans_code + 1;
                $row->save();

                return (int) $row->last_trans_code;
            }
        });
    }

    private static function currentMaxForCompany(int $companyId): int
    {
        $transactionMax = (int) Transactions::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->max('trans_code');

        $voucherMax = (int) Vouchers::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->max('trans_code');

        return max($transactionMax, $voucherMax);
    }
}
