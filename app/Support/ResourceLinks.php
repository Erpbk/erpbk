<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Durable document→invoice/cheque links stored on payments and receipts.
 *
 * Shape:
 * {
 *   "invoice_type": "rider"|…|null,
 *   "invoices": [{"id": 1, "amount": 100.0, "number": "RINV-0001"}],
 *   "cheque_id": null,
 *   "source": "payment_form"|…
 * }
 */
class ResourceLinks
{
    public const SOURCE_PAYMENT_FORM = 'payment_form';

    public const SOURCE_RECEIPT_FORM = 'receipt_form';

    public const SOURCE_CHEQUE_CLEAR = 'cheque_clear';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_ADVANCE_LOAN = 'advance_loan';

    /**
     * @param  iterable<int, object>|null  $invoices
     * @param  array<int|string, mixed>  $amounts  keyed by invoice id
     * @return array{
     *     invoice_type: ?string,
     *     invoices: list<array{id:int, amount:float, number:?string}>,
     *     cheque_id: ?int,
     *     source: string
     * }
     */
    public static function build(
        ?string $invoiceType,
        array $invoiceIds,
        array $amounts = [],
        ?iterable $invoices = null,
        ?int $chequeId = null,
        string $source = self::SOURCE_PAYMENT_FORM
    ): array {
        $byId = [];
        foreach (collect($invoices ?? []) as $invoice) {
            if ($invoice && isset($invoice->id)) {
                $byId[(int) $invoice->id] = $invoice;
            }
        }

        $rows = [];
        foreach ($invoiceIds as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            $amount = round((float) ($amounts[$id] ?? $amounts[(string) $id] ?? 0), 2);
            $invoice = $byId[$id] ?? null;
            $number = null;
            if ($invoice) {
                $number = $invoice->invoice_number ?? null;
                if (! is_string($number) || $number === '') {
                    $number = null;
                }
            }
            $rows[] = [
                'id' => $id,
                'amount' => $amount,
                'number' => $number,
            ];
        }

        return [
            'invoice_type' => $invoiceType !== null && $invoiceType !== '' ? $invoiceType : null,
            'invoices' => array_values($rows),
            'cheque_id' => $chequeId && $chequeId > 0 ? $chequeId : null,
            'source' => $source,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public static function isEmpty(?array $links): bool
    {
        if ($links === null || $links === []) {
            return true;
        }

        $invoiceIds = self::invoiceIds($links);
        $chequeId = self::chequeId($links);

        return $invoiceIds === [] && $chequeId === null;
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public static function invoiceType(?array $links): ?string
    {
        $type = $links['invoice_type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    /**
     * @param  array<string, mixed>|null  $links
     * @return list<int>
     */
    public static function invoiceIds(?array $links): array
    {
        $ids = [];
        foreach (($links['invoices'] ?? []) as $row) {
            if (is_array($row) && isset($row['id'])) {
                $id = (int) $row['id'];
                if ($id > 0) {
                    $ids[] = $id;
                }
            } elseif (is_numeric($row)) {
                $id = (int) $row;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public static function amountFor(?array $links, int $invoiceId): float
    {
        foreach (($links['invoices'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['id'] ?? 0) === $invoiceId) {
                return round((float) ($row['amount'] ?? 0), 2);
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>|null  $links
     * @return array<int, float>
     */
    public static function amountsByInvoiceId(?array $links): array
    {
        $map = [];
        foreach (($links['invoices'] ?? []) as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $id = (int) $row['id'];
            if ($id > 0) {
                $map[$id] = round((float) ($row['amount'] ?? 0), 2);
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public static function chequeId(?array $links): ?int
    {
        $id = isset($links['cheque_id']) ? (int) $links['cheque_id'] : 0;

        return $id > 0 ? $id : null;
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public static function source(?array $links): ?string
    {
        $source = $links['source'] ?? null;

        return is_string($source) && $source !== '' ? $source : null;
    }

    /**
     * Merge cheque_id into an existing links payload (or build a cheque-only payload).
     *
     * @param  array<string, mixed>|null  $links
     * @return array<string, mixed>
     */
    public static function withCheque(?array $links, int $chequeId, string $source = self::SOURCE_CHEQUE_CLEAR): array
    {
        $base = $links ?? [
            'invoice_type' => null,
            'invoices' => [],
            'cheque_id' => null,
            'source' => $source,
        ];
        $base['cheque_id'] = $chequeId > 0 ? $chequeId : null;
        if (empty($base['source'])) {
            $base['source'] = $source;
        }

        return $base;
    }

    /**
     * Whether a payment/receipt resource_links JSON contains this invoice id.
     * Suitable for whereJsonContains / JSON path queries via LIKE fallback on serialized JSON.
     */
    public static function containsInvoiceIdPayload(int $invoiceId): string
    {
        // Matches {"id":123 or "id": 123 in JSON
        return '"id":'.$invoiceId;
    }

    /**
     * True when the given table has the resource_links column (migration applied).
     */
    public static function columnExists(string $table = 'payments'): bool
    {
        static $cache = [];
        if (! array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table) && Schema::hasColumn($table, 'resource_links');
        }

        return $cache[$table];
    }

    /**
     * Add an orWhere constraint matching resource_links invoice id when the column exists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder
     */
    public static function orWhereContainsInvoiceId($query, int $invoiceId, string $table = 'payments')
    {
        if (! self::columnExists($table)) {
            return $query;
        }

        return $query->orWhere(
            'resource_links',
            'like',
            '%'.self::containsInvoiceIdPayload($invoiceId).'%'
        );
    }

    /**
     * Add a where constraint matching resource_links invoice id when the column exists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder
     */
    public static function whereContainsInvoiceId($query, int $invoiceId, string $table = 'payments')
    {
        if (! self::columnExists($table)) {
            return $query;
        }

        return $query->where(
            'resource_links',
            'like',
            '%'.self::containsInvoiceIdPayload($invoiceId).'%'
        );
    }

    /**
     * Query payments/receipts linked to a cheque via resource_links.cheque_id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function scopeForChequeId($query, int $chequeId)
    {
        return $query->where('resource_links->cheque_id', $chequeId);
    }
}
