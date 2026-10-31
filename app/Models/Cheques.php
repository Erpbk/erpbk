<?php

namespace App\Models;

use App\Support\ResourceLinks;
use App\Traits\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
        'payment_reference',
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
        'lost_date',
        'status',
        'return_reason',
        'stop_payment_reason',
        'lost_note',
        'type',
        'is_security',
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
        'lost_date' => 'datetime',
        'amount' => 'decimal:2',
        'cleared_amount' => 'decimal:2',
        'payment_reference' => 'array',
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
     * Cheque id linked to a payment/receipt clear document (resource_links or legacy voucher ref).
     */
    public static function linkedIdForDocument(Payment|Receipt $doc): ?int
    {
        $id = ResourceLinks::chequeId(
            is_array($doc->resource_links) ? $doc->resource_links : null
        );
        if ($id) {
            return $id;
        }

        $voucherId = (int) ($doc->voucher_id ?? 0);

        return $voucherId > 0 ? static::findByClearVoucherId($voucherId)?->id : null;
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
     * Clear history entries: [{date, amount, voucher_id}, ...]
     *
     * @return list<array{date:?string,amount:float,voucher_id:int}>
     */
    public function paymentReferenceEntries(): array
    {
        $refs = $this->payment_reference;
        if (! is_array($refs)) {
            return [];
        }

        $entries = [];
        foreach ($refs as $ref) {
            if (! is_array($ref)) {
                continue;
            }
            $voucherId = (int) ($ref['voucher_id'] ?? 0);
            $amount = round((float) ($ref['amount'] ?? 0), 2);
            if ($voucherId <= 0 || $amount < 0.01) {
                continue;
            }
            $date = $ref['date'] ?? null;
            $entries[] = [
                'date' => $date ? substr((string) $date, 0, 10) : null,
                'amount' => $amount,
                'voucher_id' => $voucherId,
            ];
        }

        return $entries;
    }

    /**
     * @return list<int>
     */
    public function paymentReferenceVoucherIds(): array
    {
        return collect($this->paymentReferenceEntries())
            ->pluck('voucher_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Clear amount for a payment/receipt (excludes bank charges on payments).
     */
    public static function clearAmountFromDocument(Payment|Receipt $doc): float
    {
        $amount = round((float) ($doc->amount ?? 0), 2);
        if ($doc instanceof Payment) {
            $charges = round((float) ($doc->bank_charges ?? 0), 2);

            return round(max(0, $amount - $charges), 2);
        }

        return $amount;
    }

    /**
     * Build payment_reference entries from linked clear documents (oldest first).
     *
     * @return list<array{date:?string,amount:float,voucher_id:int}>
     */
    public function buildPaymentReferenceFromDocuments(?int $excludePaymentId = null, ?int $excludeReceiptId = null): array
    {
        $docs = $this->linkedClearDocumentsQuery($excludePaymentId, $excludeReceiptId)
            ->reorder()
            ->orderBy('id')
            ->get();

        $entries = [];
        foreach ($docs as $doc) {
            $voucherId = (int) ($doc->voucher_id ?? 0);
            $clearAmount = self::clearAmountFromDocument($doc);
            if ($voucherId <= 0 || $clearAmount < 0.01) {
                continue;
            }
            $date = $doc instanceof Payment
                ? ($doc->date_of_payment ?? null)
                : ($doc->date_of_receipt ?? null);
            $entries[] = [
                'date' => $date ? substr((string) $date, 0, 10) : null,
                'amount' => $clearAmount,
                'voucher_id' => $voucherId,
            ];
        }

        return $entries;
    }

    /**
     * Find cheque that recorded a clear against the given voucher id.
     */
    public static function findByClearVoucherId(int $voucherId): ?self
    {
        if ($voucherId <= 0) {
            return null;
        }

        return static::query()
            ->whereNotNull('payment_reference')
            ->whereRaw('JSON_CONTAINS(payment_reference, ?)', [json_encode(['voucher_id' => $voucherId])])
            ->first();
    }

    /**
     * Vouchers created from clears against this cheque (latest first).
     *
     * @return Collection<int, Vouchers>
     */
    public function linkedClearVouchers(): Collection
    {
        $voucherIds = collect($this->paymentReferenceVoucherIds());

        if ($voucherIds->isEmpty()) {
            $voucherIds = $this->linkedClearDocumentsQuery()
                ->get(['voucher_id'])
                ->pluck('voucher_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        if ($voucherIds->isEmpty()) {
            return collect();
        }

        $byId = Vouchers::query()
            ->whereIn('id', $voucherIds->all())
            ->get()
            ->keyBy('id');

        return $voucherIds
            ->reverse()
            ->values()
            ->map(fn ($id) => $byId->get((int) $id))
            ->filter()
            ->values();
    }

    /**
     * Recompute payment_reference / cleared_amount / status / cleared_date from remaining linked clears.
     */
    public function recalculateClearState(?int $excludePaymentId = null, ?int $excludeReceiptId = null): void
    {
        $entries = $this->buildPaymentReferenceFromDocuments($excludePaymentId, $excludeReceiptId);
        $cleared = round(array_sum(array_column($entries, 'amount')), 2);
        $chequeAmount = round((float) $this->amount, 2);

        $update = [
            'payment_reference' => $entries === [] ? null : $entries,
            'cleared_amount' => $cleared,
            'cleared_date' => null,
        ];

        $clearStatuses = ['Issued', 'Partially Cleared', 'Cleared'];

        if ($cleared <= 0.00001) {
            $update['cleared_amount'] = 0;
            $update['payment_reference'] = null;
            if (in_array($this->status, $clearStatuses, true)) {
                $update['status'] = 'Issued';
            }
        } elseif ($cleared + 0.00001 >= $chequeAmount) {
            $update['cleared_amount'] = $chequeAmount;
            $lastDate = null;
            foreach ($entries as $entry) {
                if (! empty($entry['date'])) {
                    $lastDate = $entry['date'];
                }
            }
            $update['cleared_date'] = $lastDate;
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

        $allVoucherIds = $collection
            ->flatMap(fn (self $cheque) => $cheque->paymentReferenceVoucherIds())
            ->filter()
            ->unique()
            ->values();

        // Fallback for rows not yet backfilled: linked docs
        if ($allVoucherIds->isEmpty()) {
            $payableIds = $collection->where('type', 'payable')->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
            $receivableIds = $collection->where('type', 'receiveable')->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
            $hasPaymentLinks = Schema::hasColumn('payments', 'resource_links');
            $hasReceiptLinks = Schema::hasColumn('receipts', 'resource_links');

            if ($hasPaymentLinks && $payableIds->isNotEmpty()) {
                $allVoucherIds = $allVoucherIds->merge(
                    Payment::query()
                        ->whereIn('resource_links->cheque_id', $payableIds->all())
                        ->pluck('voucher_id')
                );
            }
            if ($hasReceiptLinks && $receivableIds->isNotEmpty()) {
                $allVoucherIds = $allVoucherIds->merge(
                    Receipt::query()
                        ->whereIn('resource_links->cheque_id', $receivableIds->all())
                        ->pluck('voucher_id')
                );
            }
            $allVoucherIds = $allVoucherIds->filter()->unique()->values();
        }

        $vouchersById = $allVoucherIds->isEmpty()
            ? collect()
            : Vouchers::query()->whereIn('id', $allVoucherIds->all())->get()->keyBy('id');

        foreach ($collection as $cheque) {
            $ids = collect($cheque->paymentReferenceVoucherIds());
            if ($ids->isEmpty()) {
                $ids = $cheque->linkedClearDocumentsQuery()
                    ->get(['voucher_id'])
                    ->pluck('voucher_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
            }

            $cheque->setRelation(
                'clear_vouchers',
                $ids->map(fn ($id) => $vouchersById->get((int) $id))->filter()->values()
            );
        }
    }
}

