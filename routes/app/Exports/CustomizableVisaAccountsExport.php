<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Helpers\Currency;
use App\Models\ExpenseAccount;
use App\Support\VisaRenewalCategoryService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;

class CustomizableVisaAccountsExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    use BindsTextColumns;

    protected $visibleColumns;

    protected $columnOrder;

    protected $filters;

    /** @var Collection<int, ExpenseAccount>|null */
    protected $preparedRows = null;

    protected array $availableColumns = [
        'person_id' => ['title' => 'Person ID'],
        'name' => ['title' => 'Name'],
        'category' => ['title' => 'Visa Category'],
        'status' => ['title' => 'Status'],
        'wps' => ['title' => 'WPS'],
        'next_unpaid' => ['title' => 'Next Unpaid Document'],
        'expiry' => ['title' => 'Expiry Document'],
        'person_code' => ['title' => 'Person Code'],
        'labour' => ['title' => 'Labour Card #'],
        'policy' => ['title' => 'Policy Number'],
        'balance' => ['title' => 'Total'],
        'paid' => ['title' => 'Paid'],
        'unpaid' => ['title' => 'Unpaid'],
    ];

    public function __construct($visibleColumns = null, $columnOrder = null, $filters = [])
    {
        $this->visibleColumns = $visibleColumns ?: array_keys($this->availableColumns);
        $this->columnOrder = $columnOrder ?: array_keys($this->availableColumns);
        $this->filters = is_array($filters) ? $filters : [];
    }

    public function collection()
    {
        if ($this->preparedRows !== null) {
            return $this->preparedRows;
        }

        $query = ExpenseAccount::query()
            ->visa()
            ->with(['rider', 'employee', 'renewalCategory'])
            ->orderByDesc('id');

        if (! empty($this->filters['name'])) {
            $query->where('name', 'like', '%' . $this->filters['name'] . '%');
        }

        if (! empty($this->filters['quick_search'])) {
            $term = trim((string) $this->filters['quick_search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhereHas('rider', function ($qr) use ($term) {
                        $qr->where('name', 'like', '%' . $term . '%')
                            ->orWhere('rider_id', 'like', '%' . $term . '%')
                            ->orWhere('person_code', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('employee', function ($qe) use ($term) {
                        $qe->where('name', 'like', '%' . $term . '%')
                            ->orWhere('employee_id', 'like', '%' . $term . '%')
                            ->orWhere('person_code', 'like', '%' . $term . '%');
                    });
            });
        }

        $accounts = $query->get();
        $grouped = $accounts->groupBy(
            fn ($r) => $r->employee_id ? 'emp:' . $r->employee_id : 'rider:' . $r->rider_id
        );

        $this->preparedRows = $grouped
            ->map(fn (Collection $personAccounts) => VisaRenewalCategoryService::latestAccountForPerson($personAccounts->first()))
            ->values();

        return $this->preparedRows;
    }

    public function map($account): array
    {
        $row = $this->buildRowData($account);
        $data = [];

        foreach ($this->columnOrder as $columnKey) {
            if (! in_array($columnKey, $this->visibleColumns, true)) {
                continue;
            }
            $data[] = $row[$columnKey] ?? '';
        }

        return $data;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->columnOrder as $columnKey) {
            if (! in_array($columnKey, $this->visibleColumns, true)) {
                continue;
            }
            $headings[] = $this->availableColumns[$columnKey]['title']
                ?? ucfirst(str_replace('_', ' ', $columnKey));
        }

        return $headings;
    }

    protected function textColumns(): array
    {
        return $this->columnLettersForKeys(
            $this->columnOrder,
            $this->visibleColumns,
            ['person_id', 'person_code', 'labour', 'policy', 'wps', 'status', 'category']
        );
    }

    /**
     * @param  Collection<int, ExpenseAccount>  $rows
     */
    public function setPreparedRows(Collection $rows): self
    {
        $this->preparedRows = $rows->values();

        return $this;
    }

    /**
     * @return array<string, string>
     */
    protected function buildRowData(ExpenseAccount $account): array
    {
        $isEmployee = ! empty($account->employee_id);
        $person = $isEmployee ? $account->employee : $account->rider;

        $hasActiveBike = ! $isEmployee && $account->rider_id
            ? company_table('bikes')
                ->where('rider_id', $account->rider_id)
                ->where('warehouse', 'Active')
                ->exists()
            : false;

        $statusLabel = $isEmployee ? 'Employee' : ($hasActiveBike ? 'Active' : 'Inactive');
        $wpsRaw = trim((string) ($person->wps ?? ''));

        $categoryId = (int) ($account->renewal_category_id ?? VisaRenewalCategoryService::defaultCategory()->id);
        $expenses = VisaRenewalCategoryService::expensesForAccountQuery(
            (int) $account->id,
            $account->rider_id ? (int) $account->rider_id : null,
            $categoryId
        )->get(['amount', 'payment_status', 'visa_status', 'date', 'billing_month', 'expiry_date']);

        $total = (float) $expenses->sum('amount');
        $paid = (float) $expenses
            ->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'paid')
            ->sum('amount');
        $unpaid = (float) $expenses
            ->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'unpaid')
            ->sum('amount');

        $nextUnpaid = $expenses
            ->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'unpaid')
            ->sortBy([
                fn ($row) => empty($row->date) ? 1 : 0,
                fn ($row) => (string) ($row->date ?? ''),
                fn ($row) => (string) ($row->billing_month ?? ''),
                fn ($row) => (int) ($row->id ?? 0),
            ])
            ->first();

        $nextUnpaidLabel = '—';
        if ($nextUnpaid) {
            $amount = Currency::formatPlain((float) ($nextUnpaid->amount ?? 0), 2);
            $nextUnpaidLabel = trim(($nextUnpaid->visa_status ?? '—') . ' (' . $amount . ')');
        }

        $expiryLabel = '—';
        $urgent = $expenses
            ->filter(fn ($row) => ! empty($row->expiry_date))
            ->sortBy(fn ($row) => (string) $row->expiry_date)
            ->first();
        if ($urgent && ! empty($urgent->expiry_date)) {
            try {
                $expiryLabel = ($urgent->visa_status ?? '—') . ' (' . \Carbon\Carbon::parse($urgent->expiry_date)->format('d M Y') . ')';
            } catch (\Throwable $e) {
                $expiryLabel = (string) ($urgent->visa_status ?? '—');
            }
        }

        return [
            'person_id' => $isEmployee
                ? (string) ($person->employee_id ?? '-')
                : (string) ($person->rider_id ?? '-'),
            'name' => (string) ($person->name ?? $account->name ?? '-'),
            'category' => (string) ($account->renewalCategory->name ?? '—'),
            'status' => $statusLabel,
            'wps' => $wpsRaw !== '' ? $wpsRaw : '—',
            'next_unpaid' => $nextUnpaidLabel,
            'expiry' => $expiryLabel,
            'person_code' => (string) ($person->person_code ?? '-'),
            'labour' => (string) ($person->labor_card_number ?? '-'),
            'policy' => $isEmployee ? '-' : (string) ($person->policy_no ?? '-'),
            'balance' => Currency::formatPlain($total, 2),
            'paid' => Currency::formatPlain($paid, 2),
            'unpaid' => Currency::formatPlain($unpaid, 2),
        ];
    }
}
