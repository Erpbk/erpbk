<?php

namespace App\Http\Controllers;

use App\Helpers\Accounts as AccountsHelper;
use App\Models\Accounts;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsReportController extends Controller
{
    /**
     * Trial Balance report: closing balance (opening + period movement) per account,
     * grouped by account type, split into Debit / Credit columns.
     */
    public function trialBalance($company_slug, Request $request)
    {
        if (! auth()->user()->hasPermissionTo('accounts_ledger_view')) {
            abort(403, 'Unauthorized action.');
        }

        $period = $this->resolvePeriod($request);

        // Cumulative balance up to the end of the period (opening + movement).
        $query = Transactions::selectRaw('account_id, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->groupBy('account_id');

        if ($period['mode'] === 'range') {
            $query->whereDate('trans_date', '<=', $period['to_date']);
        } else {
            $query->whereDate('billing_month', '<=', $period['month'] . '-01');
        }

        $balances = $query->get()->keyBy('account_id');

        [$groups, $totalDebit, $totalCredit] = $this->buildTrialBalanceGroups($balances);

        return view('accounts.reports.trial_balance', [
            'groups' => $groups,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'period' => $period,
        ]);
    }

    /**
     * Profit & Loss statement: period movement of Revenue and Expense accounts,
     * with a Net Profit / (Loss) figure.
     */
    public function profitLoss($company_slug, Request $request)
    {
        if (! auth()->user()->hasPermissionTo('accounts_ledger_view')) {
            abort(403, 'Unauthorized action.');
        }

        $period = $this->resolvePeriod($request);

        // Period movement only (not cumulative) for income-statement accounts.
        $query = Transactions::selectRaw('account_id, SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->groupBy('account_id');

        if ($period['mode'] === 'range') {
            $query->whereBetween('trans_date', [$period['from_date'], $period['to_date']]);
        } else {
            $query->whereDate('billing_month', $period['month'] . '-01');
        }

        $balances = $query->get()->keyBy('account_id');

        [$revenue, $expense, $totalRevenue, $totalExpense] = $this->buildProfitLossSections($balances);

        return view('accounts.reports.profit_loss', [
            'revenue' => $revenue,
            'expense' => $expense,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalRevenue - $totalExpense,
            'period' => $period,
        ]);
    }

    /**
     * Resolve the reporting period from the request.
     * Prefers an explicit date range, otherwise falls back to a billing month
     * (defaulting to the current month), mirroring the Ledger.
     */
    private function resolvePeriod(Request $request): array
    {
        $fromDate = trim((string) $request->input('from_date'));
        $toDate = trim((string) $request->input('to_date'));

        if ($fromDate !== '' && $toDate !== '') {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->startOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return [
                'mode' => 'range',
                'from_date' => $from->format('Y-m-d'),
                'to_date' => $to->format('Y-m-d'),
                'month' => null,
                'label' => $from->format('d M Y') . ' - ' . $to->format('d M Y'),
            ];
        }

        $month = trim((string) $request->input('month'));
        if ($month === '' || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::now()->format('Y-m');
        }

        return [
            'mode' => 'month',
            'from_date' => null,
            'to_date' => null,
            'month' => $month,
            'label' => Carbon::parse($month . '-01')->format('F Y'),
        ];
    }

    private function buildTrialBalanceGroups($balances): array
    {
        if ($balances->isEmpty()) {
            return [[], 0.0, 0.0];
        }

        $activeIds = [];
        foreach ($balances as $accountId => $row) {
            $net = (float) ($row->debit_sum ?? 0) - (float) ($row->credit_sum ?? 0);
            if (abs($net) >= 0.005) {
                $activeIds[] = (int) $accountId;
            }
        }

        if (empty($activeIds)) {
            return [[], 0.0, 0.0];
        }

        $accounts = Accounts::whereIn('id', $activeIds)->get()->keyBy('id');
        $ancestorIds = $accounts->pluck('parent_id')
            ->filter(fn ($id) => ! empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        while (! empty($ancestorIds)) {
            $missingAncestorIds = array_values(array_diff($ancestorIds, $accounts->keys()->all()));
            if (empty($missingAncestorIds)) {
                break;
            }

            $ancestorAccounts = Accounts::whereIn('id', $missingAncestorIds)->get()->keyBy('id');
            if ($ancestorAccounts->isEmpty()) {
                break;
            }

            $accounts = $accounts->merge($ancestorAccounts);
            $ancestorIds = $ancestorAccounts->pluck('parent_id')
                ->filter(fn ($id) => ! empty($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $accounts = $accounts->sortBy([
            ['account_type', 'asc'],
            ['account_code', 'asc'],
        ])->keyBy('id');

        $nodes = [];
        foreach ($accounts as $account) {
            $row = $balances->get($account->id);
            $net = (float) ($row->debit_sum ?? 0) - (float) ($row->credit_sum ?? 0);
            $ownDebit = $net > 0 ? $net : 0.0;
            $ownCredit = $net < 0 ? abs($net) : 0.0;

            $nodes[$account->id] = [
                'id' => $account->id,
                'parent_id' => $account->parent_id ? (int) $account->parent_id : null,
                'type' => $account->account_type ?: 'Uncategorized',
                'code' => $account->account_code,
                'name' => $account->name,
                'own_debit' => $ownDebit,
                'own_credit' => $ownCredit,
                'subtotal_debit' => $ownDebit,
                'subtotal_credit' => $ownCredit,
                'children' => [],
            ];
        }

        $roots = [];
        foreach ($nodes as $id => $node) {
            $parentId = $node['parent_id'];
            if ($parentId && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = $id;
            } else {
                $roots[] = $id;
            }
        }

        foreach ($nodes as &$node) {
            usort($node['children'], function ($left, $right) use ($nodes) {
                return strcmp((string) $nodes[$left]['code'], (string) $nodes[$right]['code']);
            });
        }
        unset($node);

        $typeOrder = array_keys(AccountsHelper::AccountTypes());
        $groupedRoots = [];
        foreach ($roots as $rootId) {
            $tree = $this->buildTrialBalanceNode($rootId, $nodes, 0);
            if ($tree === null) {
                continue;
            }
            $groupedRoots[$tree['type']][] = $tree;
        }

        uksort($groupedRoots, function ($a, $b) use ($typeOrder) {
            $ia = array_search($a, $typeOrder);
            $ib = array_search($b, $typeOrder);
            $ia = $ia === false ? PHP_INT_MAX : $ia;
            $ib = $ib === false ? PHP_INT_MAX : $ib;

            return $ia <=> $ib ?: strcmp($a, $b);
        });

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($groupedRoots as $trees) {
            foreach ($trees as $tree) {
                $totalDebit += $tree['subtotal_debit'];
                $totalCredit += $tree['subtotal_credit'];
            }
        }

        return [$groupedRoots, $totalDebit, $totalCredit];
    }

    private function buildTrialBalanceNode(int $nodeId, array $nodes, int $level): ?array
    {
        $node = $nodes[$nodeId];
        $children = [];
        $subtotalDebit = $node['own_debit'];
        $subtotalCredit = $node['own_credit'];

        foreach ($node['children'] as $childId) {
            $childNode = $this->buildTrialBalanceNode($childId, $nodes, $level + 1);
            if ($childNode === null) {
                continue;
            }

            $children[] = $childNode;
            $subtotalDebit += $childNode['subtotal_debit'];
            $subtotalCredit += $childNode['subtotal_credit'];
        }

        if (abs($subtotalDebit) < 0.005 && abs($subtotalCredit) < 0.005) {
            return null;
        }

        $node['level'] = $level;
        $node['children'] = $children;
        $node['subtotal_debit'] = $subtotalDebit;
        $node['subtotal_credit'] = $subtotalCredit;

        return $node;
    }

    /**
     * Build Revenue/Expense parent-child trees for Profit & Loss.
     * For expanded nodes we show each account's own amount; for collapsed nodes we show subtree totals.
     */
    private function buildProfitLossSections($balances): array
    {
        $targetTypes = ['Revenue', 'Expense'];

        if ($balances->isEmpty()) {
            return [[], [], 0.0, 0.0];
        }

        $accountsBase = Accounts::whereIn('id', $balances->keys()->all())
            ->whereIn('account_type', $targetTypes)
            ->get()
            ->keyBy('id');

        if ($accountsBase->isEmpty()) {
            return [[], [], 0.0, 0.0];
        }

        $activeIds = [];
        foreach ($accountsBase as $accountId => $account) {
            $row = $balances->get($accountId);
            $debit = (float) ($row->debit_sum ?? 0);
            $credit = (float) ($row->credit_sum ?? 0);

            $amount = $account->account_type === 'Revenue'
                ? ($credit - $debit)
                : ($debit - $credit);

            if (abs($amount) >= 0.005) {
                $activeIds[] = (int) $accountId;
            }
        }

        if (empty($activeIds)) {
            return [[], [], 0.0, 0.0];
        }

        // Include ancestors (same target types) so the tree has correct nesting.
        $accounts = Accounts::whereIn('id', $activeIds)->get()->keyBy('id');
        $ancestorIds = $accounts->pluck('parent_id')
            ->filter(fn ($id) => ! empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        while (! empty($ancestorIds)) {
            $missingAncestorIds = array_values(array_diff($ancestorIds, $accounts->keys()->all()));
            if (empty($missingAncestorIds)) {
                break;
            }

            $ancestorAccounts = Accounts::whereIn('id', $missingAncestorIds)
                ->whereIn('account_type', $targetTypes)
                ->get()
                ->keyBy('id');

            if ($ancestorAccounts->isEmpty()) {
                break;
            }

            $accounts = $accounts->merge($ancestorAccounts);
            $ancestorIds = $ancestorAccounts->pluck('parent_id')
                ->filter(fn ($id) => ! empty($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $nodes = [];
        foreach ($accounts as $account) {
            $row = $balances->get($account->id);
            $debit = (float) ($row->debit_sum ?? 0);
            $credit = (float) ($row->credit_sum ?? 0);

            $ownAmount = $account->account_type === 'Revenue'
                ? ($credit - $debit)
                : ($debit - $credit);

            $nodes[$account->id] = [
                'id' => $account->id,
                'parent_id' => $account->parent_id ? (int) $account->parent_id : null,
                'type' => $account->account_type ?: 'Uncategorized',
                'code' => $account->account_code,
                'name' => $account->name,
                'own_amount' => $ownAmount,
                'subtotal_amount' => $ownAmount,
                'children' => [],
            ];
        }

        $rootsRevenue = [];
        $rootsExpense = [];
        foreach ($nodes as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = $id;
            } else {
                if ($node['type'] === 'Revenue') {
                    $rootsRevenue[] = $id;
                } else {
                    $rootsExpense[] = $id;
                }
            }
        }
        unset($node);

        // Sort roots and children by account_code.
        sort($rootsRevenue);
        sort($rootsExpense);

        foreach ($nodes as &$node) {
            usort($node['children'], function ($left, $right) use ($nodes) {
                return strcmp((string) $nodes[$left]['code'], (string) $nodes[$right]['code']);
            });
        }
        unset($node);

        usort($rootsRevenue, function ($a, $b) use ($nodes) {
            return strcmp((string) $nodes[$a]['code'], (string) $nodes[$b]['code']);
        });
        usort($rootsExpense, function ($a, $b) use ($nodes) {
            return strcmp((string) $nodes[$a]['code'], (string) $nodes[$b]['code']);
        });

        $revenueTrees = [];
        foreach ($rootsRevenue as $rootId) {
            $tree = $this->buildProfitLossNode($rootId, $nodes, 0);
            if ($tree !== null) {
                $revenueTrees[] = $tree;
            }
        }

        $expenseTrees = [];
        foreach ($rootsExpense as $rootId) {
            $tree = $this->buildProfitLossNode($rootId, $nodes, 0);
            if ($tree !== null) {
                $expenseTrees[] = $tree;
            }
        }

        $totalRevenue = 0.0;
        foreach ($revenueTrees as $tree) {
            $totalRevenue += (float) ($tree['subtotal_amount'] ?? 0);
        }

        $totalExpense = 0.0;
        foreach ($expenseTrees as $tree) {
            $totalExpense += (float) ($tree['subtotal_amount'] ?? 0);
        }

        return [$revenueTrees, $expenseTrees, $totalRevenue, $totalExpense];
    }

    private function buildProfitLossNode(int $nodeId, array $nodes, int $level): ?array
    {
        $node = $nodes[$nodeId];
        $childrenOut = [];
        $subtotalAmount = (float) ($node['own_amount'] ?? 0);

        foreach ($node['children'] as $childId) {
            $childNode = $this->buildProfitLossNode($childId, $nodes, $level + 1);
            if ($childNode === null) {
                continue;
            }
            $childrenOut[] = $childNode;
            $subtotalAmount += (float) ($childNode['subtotal_amount'] ?? 0);
        }

        if (abs($subtotalAmount) < 0.005 && empty($childrenOut)) {
            return null;
        }

        $node['level'] = $level;
        $node['children'] = $childrenOut;
        $node['subtotal_amount'] = $subtotalAmount;

        return $node;
    }
}
