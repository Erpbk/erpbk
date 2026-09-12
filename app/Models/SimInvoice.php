<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class SimInvoice extends BaseModel
{
    use SoftDeletes, LogsActivity;

    public $table = 'sim_invoices';

    public $fillable = [
        'inv_date',
        'vendor_id',
        'billing_month',
        'invoice_number',
        'reference_number',
        'descriptions',
        'subtotal',
        'vat',
        'total_amount',
        'notes',
        'attachment',
        'status',
        'partial_paid_amount',
    ];

    protected $casts = [
        'inv_date' => 'date',
        'billing_month' => 'date',
        'attachment' => 'string',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'integer',
        'partial_paid_amount' => 'array',
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'inv_date' => 'required|date',
        'company_id' => 'required|exists:sim_companies,id',
        'billing_month' => 'required|date',
        'invoice_number' => 'nullable|string|max:255',
        'reference_number' => 'required|string|max:255',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        'status' => 'nullable|integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (self $invoice) {
            if (!empty($invoice->invoice_number)) {
                return;
            }

            $invoice->invoice_number = 'SIMI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT);
            $invoice->saveQuietly();
        });
    }

    public function company()
    {
        return $this->belongsTo(SimCompany::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(SimInvoiceItem::class, 'inv_id', 'id');
    }

    /**
     * Build show/edit pivot: unique charge items as columns, one row per SIM.
     *
     * @return array{columns: \Illuminate\Support\Collection, rows: array<int, array{sim_id:int,sim:?Sims,charges:array<int,float>,vat:float,total:float,vat_percent:float}>}
     */
    public function pivotChargeGrid(): array
    {
        $this->loadMissing(['items.item', 'items.sim']);

        $columns = $this->items
            ->pluck('item')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        if ($columns->isEmpty()) {
            $itemIds = $this->items->pluck('item_id')->unique()->filter()->values();
            $columns = Items::whereIn('id', $itemIds)->orderBy('name')->get();
        }

        $rows = [];
        foreach ($this->items->groupBy('sim_id') as $simId => $lines) {
            $charges = [];
            $qtys = [];
            $vat = 0.0;
            $total = 0.0;
            $excl = 0.0;
            foreach ($lines as $line) {
                $itemId = (int) $line->item_id;
                $lineExcl = ((float) $line->qty * (float) $line->rate) - (float) $line->discount;
                $charges[$itemId] = round($lineExcl, 2);
                $qtys[$itemId] = round((float) $line->qty, 2);
                $vat += (float) $line->tax;
                $total += (float) $line->amount;
                $excl += $lineExcl;
            }
            $rows[] = [
                'sim_id' => (int) $simId,
                'sim' => $lines->first()->sim,
                'charges' => $charges,
                'qtys' => $qtys,
                'vat' => round($vat, 2),
                'total' => round($total, 2),
                'vat_percent' => $excl > 0 ? round(($vat / $excl) * 100, 2) : 0.0,
            ];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public static function getIdFromInvoiceNumber($invoiceNumber)
    {
        $numericPart = preg_replace('/^SIMI-?/i', '', (string) $invoiceNumber);
        $id = (int) ltrim($numericPart, '0');

        return self::where('id', $id)->exists() ? $id : null;
    }

    public function getPaidAmountAttribute()
    {
        return array_sum($this->partial_paid_amount ?? []);
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * After the invoice is actually soft-deleted (approval or bypass), soft-delete
     * ledger rows so they stay recoverable. Line items and attachments stay until
     * permanent Recycle Bin destroy.
     */
    public function finalizeSoftDeletion(?int $deletedBy = null): void
    {
        $wasBypassing = \App\Services\DeleteRequestService::isBypassing();
        \App\Services\DeleteRequestService::bypass(true);

        try {
            $transactions = Transactions::withTrashed()
                ->withoutGlobalScope('branch')
                ->where('reference_type', 'SimInvoice')
                ->where('reference_id', $this->id)
                ->get();

            foreach ($transactions as $transaction) {
                if (! $transaction->trashed()) {
                    if ($deletedBy && Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
                        $transaction->deleted_by = $deletedBy;
                        $transaction->save();
                    }

                    $transaction->delete();
                }

                try {
                    DeletionCascade::logCascade(
                        self::class,
                        $this->id,
                        $this->invoice_number ?: ('SIMI-' . $this->id),
                        Transactions::class,
                        $transaction->id,
                        'Transaction #' . $transaction->id,
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion of SIM invoice ledger'
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Failed to track SIM invoice cascade', [
                        'invoice_id' => $this->id,
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            if (! $wasBypassing) {
                \App\Services\DeleteRequestService::bypass(false);
            }
        }
    }
}
