<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use App\Traits\BranchScope;

class Cheques extends BaseModel
{
    use HasFactory, SoftDeletes, BranchScope;

    protected $table = 'cheques';

    protected $fillable = [
        'branch_id',
        'cheque_number',
        'bank_id',
        'amount',
        'cleared_amount',
        'payee_account',
        'payee_name',
        'payer_account',
        'payer_name',
        'reference',
        'attachment',
        'description',
        'issue_date',
        'cheque_date',
        'cleared_date',
        'returned_date',
        'stop_payment_date',
        'billing_month',
        'status',
        'return_reason',
        'type',
        'is_security',
        'voucher_id',
        'cheque_top_option_id',
        'issued_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'cheque_date' => 'datetime',
        'cleared_date' => 'datetime',
        'returned_date' => 'datetime',
        'stop_payment_date' => 'datetime',
        'billing_month' => 'datetime',
        'amount' => 'decimal:2',
        'cleared_amount' => 'decimal:2',
        'is_security' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'branch_id' => 'nullable',
        'cheque_number' => 'required|string',
        'bank_id' => 'required|exists:banks,id',
        'amount' => 'required|numeric|min:0',
        'payee_name' => 'nullable|string|max:255',
        'payer_name' => 'nullable|string|max:255',
        'issue_date' => 'required|date',
        'status' => 'required|in:Issued,Partially Cleared,Cleared,Returned,Stop Payment,Lost',
        'type' => 'required|in:payable,receiveable',
        'created_by' => 'required|exists:users,id',
    ];

    public function bank()
    {
        return $this->belongsTo(Banks::class, 'bank_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Vouchers::class, 'voucher_id');
    }

    public function payer()
    {
        return $this->belongsTo(Accounts::class, 'payer_account');
    }

    public function payee()
    {
        return $this->belongsTo(Accounts::class, 'payee_account');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function chequeTopOption()
    {
        return $this->belongsTo(ChequeTopOption::class, 'cheque_top_option_id');
    }

    public function Created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function Updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getBranchNameAttribute()
    {
        return $this->branch_id
            ? $this->branch->name.' ( '.$this->branch->code.' )'
            : 'All';
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - (float) $this->cleared_amount, 2));
    }

    public function isPayable(): bool
    {
        return $this->type === 'payable';
    }

    /**
     * Payments or receipts linked via resource_links.cheque_id.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function linkedClearDocumentsQuery(?int $excludePaymentId = null, ?int $excludeReceiptId = null)
    {
        if ($this->isPayable()) {
            $query = Payment::query()->where('resource_links->cheque_id', (int) $this->id);
            if ($excludePaymentId) {
                $query->where('id', '!=', $excludePaymentId);
            }

            return $query->orderByDesc('id');
        }

        $query = Receipt::query()->where('resource_links->cheque_id', (int) $this->id);
        if ($excludeReceiptId) {
            $query->where('id', '!=', $excludeReceiptId);
        }

        return $query->orderByDesc('id');
    }

    /**
     * Vouchers created from clears against this cheque (latest first).
     * Falls back to the single voucher_id for legacy rows without resource_links.
     *
     * @return Collection<int, Vouchers>
     */
    public function linkedClearVouchers(): Collection
    {
        $docs = $this->linkedClearDocumentsQuery()->get(['id', 'amount', 'voucher_id']);
        $voucherIds = $docs->pluck('voucher_id')->filter()->unique()->values();

        if ($voucherIds->isEmpty() && $this->voucher_id) {
            $voucherIds = collect([(int) $this->voucher_id]);
        }

        if ($voucherIds->isEmpty()) {
            return collect();
        }

        $byId = Vouchers::query()
            ->whereIn('id', $voucherIds->all())
            ->get()
            ->keyBy('id');

        return $voucherIds
            ->map(fn ($id) => $byId->get((int) $id))
            ->filter()
            ->values();
    }

    /**
     * Recompute cleared_amount / status / voucher_id from remaining linked clear documents.
     */
    public function recalculateClearState(?int $excludePaymentId = null, ?int $excludeReceiptId = null): void
    {
        $docs = $this->linkedClearDocumentsQuery($excludePaymentId, $excludeReceiptId)
            ->get(['id', 'amount', 'voucher_id']);

        $cleared = round((float) $docs->sum('amount'), 2);
        $chequeAmount = round((float) $this->amount, 2);
        $latestVoucherId = $docs->first()?->voucher_id;

        $update = [
            'cleared_amount' => $cleared,
            'voucher_id' => $latestVoucherId,
        ];

        $clearStatuses = ['Issued', 'Partially Cleared', 'Cleared'];

        if ($cleared <= 0.00001) {
            $update['cleared_amount'] = 0;
            $update['voucher_id'] = null;
            $update['cleared_date'] = null;
            $update['billing_month'] = null;
            if (in_array($this->status, $clearStatuses, true)) {
                $update['status'] = 'Issued';
            }
        } elseif ($cleared + 0.00001 >= $chequeAmount) {
            $update['cleared_amount'] = $chequeAmount;
            if (in_array($this->status, $clearStatuses, true)) {
                $update['status'] = 'Cleared';
            }
        } else {
            if (in_array($this->status, $clearStatuses, true)) {
                $update['status'] = 'Partially Cleared';
            }
        }

        $this->update($update);
    }

    /**
     * Batch-attach clear vouchers onto a cheque collection as `clear_vouchers`.
     *
     * @param  iterable<int, self>  $cheques
     */
    /**
     * @param  iterable<int, self>|\Illuminate\Contracts\Pagination\Paginator  $cheques
     */
    public static function attachClearVouchersTo(iterable $cheques): void
    {
        // Paginator::toArray() includes scalars (current_page, etc.) — use items only.
        if ($cheques instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $items = $cheques->items();
        } else {
            $items = $cheques;
        }

        $collection = collect($items)->filter(fn ($cheque) => $cheque instanceof self)->values();
        if ($collection->isEmpty()) {
            return;
        }

        $payableIds = $collection->where('type', 'payable')->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
        $receivableIds = $collection->where('type', 'receiveable')->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();

        $hasPaymentLinks = Schema::hasColumn('payments', 'resource_links');
        $hasReceiptLinks = Schema::hasColumn('receipts', 'resource_links');

        $paymentsByCheque = collect();
        if ($hasPaymentLinks && $payableIds->isNotEmpty()) {
            $paymentsByCheque = Payment::query()
                ->whereIn('resource_links->cheque_id', $payableIds->all())
                ->get(['id', 'amount', 'voucher_id', 'resource_links'])
                ->groupBy(fn (Payment $p) => (int) data_get($p->resource_links, 'cheque_id'));
        }

        $receiptsByCheque = collect();
        if ($hasReceiptLinks && $receivableIds->isNotEmpty()) {
            $receiptsByCheque = Receipt::query()
                ->whereIn('resource_links->cheque_id', $receivableIds->all())
                ->get(['id', 'amount', 'voucher_id', 'resource_links'])
                ->groupBy(fn (Receipt $r) => (int) data_get($r->resource_links, 'cheque_id'));
        }

        $allVoucherIds = $paymentsByCheque->flatten(1)->pluck('voucher_id')
            ->merge($receiptsByCheque->flatten(1)->pluck('voucher_id'))
            ->merge($collection->pluck('voucher_id'))
            ->filter()
            ->unique()
            ->values();

        $vouchersById = $allVoucherIds->isEmpty()
            ? collect()
            : Vouchers::query()->whereIn('id', $allVoucherIds->all())->get()->keyBy('id');

        foreach ($collection as $cheque) {
            $docs = $cheque->type === 'payable'
                ? ($paymentsByCheque->get((int) $cheque->id) ?? collect())
                : ($receiptsByCheque->get((int) $cheque->id) ?? collect());

            $ids = $docs->pluck('voucher_id')->filter()->unique()->values();
            if ($ids->isEmpty() && $cheque->voucher_id) {
                $ids = collect([(int) $cheque->voucher_id]);
            }

            $cheque->setRelation(
                'clear_vouchers',
                $ids->map(fn ($id) => $vouchersById->get((int) $id))->filter()->values()
            );
        }
    }
}
