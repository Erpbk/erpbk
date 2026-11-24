<?php

namespace App\Support;

use App\Models\Accounts;
use App\Models\Transactions;
use App\Models\Vouchers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve Bank / Cash / Account kind and Paid From accounts for voucher list UI.
 * Mirrors the vouchers-demo HTML rules: cash/petty names → cash, BK* → bank, else Account.
 */
final class VoucherAccountKind
{
    public const KIND_CASH = 'cash';

    public const KIND_BANK = 'bank';

    public const KIND_OTHER = 'oth';

    /** @var array<int, string>|null */
    private static ?array $kindByAccountId = null;

    /** @var array<int, string>|null */
    private static ?array $labelByAccountId = null;

    /**
     * @return array{cash: array<int, int>, bank: array<int, int>, oth: array<int, int>}
     */
    public static function accountIdsByKind(): array
    {
        self::ensureAccountMaps();

        $grouped = [
            self::KIND_CASH => [],
            self::KIND_BANK => [],
            self::KIND_OTHER => [],
        ];

        foreach (self::$kindByAccountId ?? [] as $id => $kind) {
            $grouped[$kind][] = (int) $id;
        }

        return $grouped;
    }

    public static function classifyAccount(?Accounts $account): string
    {
        if (! $account) {
            return self::KIND_OTHER;
        }

        self::ensureAccountMaps();

        return self::$kindByAccountId[(int) $account->id] ?? self::classifyFromFields(
            (string) ($account->account_code ?? ''),
            (string) ($account->name ?? ''),
            $account->parent_id !== null ? (int) $account->parent_id : null
        );
    }

    public static function classifyFromFields(string $code, string $name, ?int $parentId = null): string
    {
        $name = trim($name);
        $code = trim($code);

        // Chart headers like "Cash & Bank" are parents, not paid-from leaves.
        if ($name !== '' && preg_match('/cash\s*&\s*banks?/i', $name) && ! preg_match('/petty|in\s*hand/i', $name)) {
            return self::KIND_OTHER;
        }

        $hay = trim($code.' '.$name);
        if ($hay !== '' && preg_match('/cash|petty/i', $hay)) {
            return self::KIND_CASH;
        }

        $cashParent = GlobalAccounts::idOrNull('CASH');
        if ($cashParent && $parentId === (int) $cashParent) {
            return self::KIND_CASH;
        }

        $bankParent = GlobalAccounts::idOrNull('BANK');
        if (($bankParent && $parentId === (int) $bankParent) || preg_match('/^BK/i', $code)) {
            return self::KIND_BANK;
        }

        return self::KIND_OTHER;
    }

    public static function labelForKind(string $kind): string
    {
        return match ($kind) {
            self::KIND_CASH => 'Cash',
            self::KIND_BANK => 'Bank',
            default => 'Account',
        };
    }

    /**
     * Resolve voucher branch_id from line accounts.
     * If any line is a bank account → second account's branch_id;
     * otherwise → first account's branch_id. Falls back when the target has none.
     *
     * @param  array<int, mixed>  $accountIds
     */
    public static function resolveVoucherBranchId(array $accountIds, ?int $fallback = null): ?int
    {
        $ids = [];
        foreach ($accountIds as $accountId) {
            $id = (int) $accountId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values($ids);
        if ($ids === []) {
            return $fallback;
        }

        $accounts = Accounts::query()
            ->whereIn('id', $ids)
            ->get(['id', 'branch_id', 'parent_id', 'account_code', 'name']);

        $hasBank = $accounts->contains(
            fn (Accounts $account) => self::classifyAccount($account) === self::KIND_BANK
        );

        $preferredIds = $hasBank
            ? array_values(array_unique(array_filter([
                $ids[1] ?? null,
                $ids[0] ?? null,
            ])))
            : [$ids[0]];

        foreach ($preferredIds as $preferredId) {
            $account = $accounts->firstWhere('id', (int) $preferredId);
            if ($account && ! empty($account->branch_id)) {
                return (int) $account->branch_id;
            }
        }

        return $fallback;
    }

    /**
     * Restrict a vouchers query to rows whose paid-from account matches $kind.
     */
    public static function applyKindFilter(Builder $query, string $kind): Builder
    {
        $kind = strtolower(trim($kind));
        if (! in_array($kind, [self::KIND_CASH, self::KIND_BANK, self::KIND_OTHER], true)) {
            return $query;
        }

        $byKind = self::accountIdsByKind();
        $cashIds = $byKind[self::KIND_CASH] ?? [];
        $bankIds = $byKind[self::KIND_BANK] ?? [];
        $bankCashIds = array_values(array_unique(array_merge($cashIds, $bankIds)));

        if ($kind === self::KIND_OTHER) {
            return $query->where(function (Builder $q) use ($bankCashIds) {
                // Explicit non-bank/cash payment_from
                $q->where(function (Builder $a) use ($bankCashIds) {
                    $a->whereNotNull('payment_from')->where('payment_from', '>', 0);
                    if ($bankCashIds !== []) {
                        $a->whereNotIn('payment_from', $bankCashIds);
                    }
                })->orWhere(function (Builder $b) use ($bankCashIds) {
                    // No payment_from — treat as Account only if no bank/cash credits
                    $b->where(function (Builder $pf) {
                        $pf->whereNull('payment_from')->orWhere('payment_from', 0);
                    });
                    if ($bankCashIds !== []) {
                        $b->whereNotExists(function ($sub) use ($bankCashIds) {
                            self::creditAccountExistsSubquery($sub, $bankCashIds);
                        });
                    }
                });
            });
        }

        $ids = $kind === self::KIND_CASH ? $cashIds : $bankIds;
        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $outer) use ($ids) {
            $outer->whereIn('payment_from', $ids)
                ->orWhereExists(function ($sub) use ($ids) {
                    self::creditAccountExistsSubquery($sub, $ids);
                });
        });
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $sub
     * @param  array<int, int>  $accountIds
     */
    private static function creditAccountExistsSubquery($sub, array $accountIds): void
    {
        $sub->selectRaw('1')
            ->from('transactions')
            ->whereColumn('transactions.trans_code', 'vouchers.trans_code')
            ->where('transactions.credit', '>', 0)
            ->whereIn('transactions.account_id', $accountIds);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $sub->where(function ($q) {
                $q->whereNull('transactions.deleted_at');
                if (Schema::hasColumn('transactions', 'approval_held_at')) {
                    $q->orWhereNotNull('transactions.approval_held_at');
                }
            });
        }
    }

    /**
     * Enrich a page of vouchers with list_account_kind + list_paid_from for the table.
     *
     * @param  iterable<int, Vouchers>  $vouchers
     */
    public static function hydrateListRows(iterable $vouchers): void
    {
        $items = collect($vouchers)->filter();
        if ($items->isEmpty()) {
            return;
        }

        self::ensureAccountMaps();

        $needTx = $items->filter(function (Vouchers $v) {
            return empty($v->payment_from);
        });

        /** @var Collection<string|int, Collection<int, object>> $creditsByCode */
        $creditsByCode = collect();
        if ($needTx->isNotEmpty()) {
            $codes = $needTx->pluck('trans_code')->filter()->unique()->values()->all();
            if ($codes !== []) {
                $txQuery = Transactions::query()->withTrashed()
                    ->whereIn('trans_code', $codes)
                    ->where('credit', '>', 0)
                    ->orderBy('id');
                self::constrainCreditTransactions($txQuery);

                $creditsByCode = $txQuery->get(['trans_code', 'account_id', 'credit'])
                    ->groupBy('trans_code');
            }
        }

        $accountIds = $items->pluck('payment_from')
            ->merge($creditsByCode->flatten(1)->pluck('account_id'))
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($accountIds !== []) {
            // Ensure labels exist even if maps were built earlier without these IDs.
            $missing = array_diff($accountIds, array_keys(self::$labelByAccountId ?? []));
            if ($missing !== []) {
                Accounts::query()
                    ->whereIn('id', $missing)
                    ->get(['id', 'account_code', 'name', 'parent_id'])
                    ->each(function (Accounts $acc) {
                        $id = (int) $acc->id;
                        self::$labelByAccountId[$id] = trim(($acc->account_code ? $acc->account_code.' - ' : '').$acc->name);
                        self::$kindByAccountId[$id] = self::classifyFromFields(
                            (string) ($acc->account_code ?? ''),
                            (string) ($acc->name ?? ''),
                            $acc->parent_id !== null ? (int) $acc->parent_id : null
                        );
                    });
            }
        }

        foreach ($items as $voucher) {
            $paidFromIds = [];
            $pf = (int) ($voucher->payment_from ?? 0);
            if ($pf > 0) {
                $paidFromIds[] = $pf;
            } else {
                $rows = $creditsByCode->get($voucher->trans_code, collect());
                $bankCash = $rows->filter(function ($row) {
                    $kind = self::$kindByAccountId[(int) $row->account_id] ?? self::KIND_OTHER;

                    return in_array($kind, [self::KIND_CASH, self::KIND_BANK], true);
                });
                $use = $bankCash->isNotEmpty() ? $bankCash : $rows;
                $paidFromIds = $use->pluck('account_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            }

            $entries = [];
            foreach ($paidFromIds as $aid) {
                $kind = self::$kindByAccountId[$aid] ?? self::KIND_OTHER;
                $entries[] = [
                    'id' => $aid,
                    'label' => self::$labelByAccountId[$aid] ?? ('#'.$aid),
                    'kind' => $kind,
                ];
            }

            $kind = self::KIND_OTHER;
            foreach ($entries as $entry) {
                if ($entry['kind'] === self::KIND_CASH) {
                    $kind = self::KIND_CASH;
                    break;
                }
                if ($entry['kind'] === self::KIND_BANK) {
                    $kind = self::KIND_BANK;
                }
            }

            $voucher->setAttribute('list_account_kind', $kind);
            $voucher->setAttribute('list_paid_from', $entries);
        }
    }

    /**
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     */
    private static function constrainCreditTransactions($query): void
    {
        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('deleted_at');
            if (Schema::hasColumn('transactions', 'approval_held_at')) {
                $q->orWhereNotNull('approval_held_at');
            }
        });
    }

    private static function ensureAccountMaps(): void
    {
        if (self::$kindByAccountId !== null) {
            return;
        }

        self::$kindByAccountId = [];
        self::$labelByAccountId = [];

        $bankParent = GlobalAccounts::idOrNull('BANK');
        $cashParent = GlobalAccounts::idOrNull('CASH');

        $query = Accounts::query()->select(['id', 'account_code', 'name', 'parent_id']);
        $query->where(function (Builder $q) use ($bankParent, $cashParent) {
            $q->where('account_code', 'like', 'BK%')
                ->orWhere('name', 'like', '%Cash%')
                ->orWhere('name', 'like', '%Petty%')
                ->orWhere('name', 'like', '%cash%')
                ->orWhere('name', 'like', '%petty%');
            if ($bankParent) {
                $q->orWhere('parent_id', $bankParent);
            }
            if ($cashParent) {
                $q->orWhere('parent_id', $cashParent);
            }
        });

        $bankParent = $bankParent ? (int) $bankParent : null;
        $cashParent = $cashParent ? (int) $cashParent : null;

        foreach ($query->get() as $acc) {
            $id = (int) $acc->id;
            // Never treat configured BANK/CASH parents as paid-from leaves.
            if (($bankParent && $id === $bankParent) || ($cashParent && $id === $cashParent)) {
                continue;
            }
            self::$labelByAccountId[$id] = trim(($acc->account_code ? $acc->account_code.' - ' : '').$acc->name);
            self::$kindByAccountId[$id] = self::classifyFromFields(
                (string) ($acc->account_code ?? ''),
                (string) ($acc->name ?? ''),
                $acc->parent_id !== null ? (int) $acc->parent_id : null
            );
        }
    }
}
