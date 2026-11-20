<?php

namespace App\Services\Voucher;

use App\Helpers\Account;
use App\Models\Riders;
use App\Models\Vouchers;
use App\Services\TransactionService;
use App\Support\ExcelDate;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

final class VoucherImportGridService
{
    /**
     * Parse uploaded Excel/CSV into editable grid rows (no DB writes).
     *
     * @return array{rows: list<array<string, mixed>>, counts: array{ok:int,err:int,warn:int}}
     */
    public function preview(UploadedFile $file): array
    {
        $collector = new class implements ToCollection
        {
            public Collection $rows;

            public function collection(Collection $rows): void
            {
                $this->rows = $rows;
            }
        };

        Excel::import($collector, $file);
        $raw = $collector->rows ?? collect();

        $parsed = [];
        foreach ($raw as $index => $row) {
            // Skip header rows (first two), same as legacy ImportVoucher.
            if ($index < 2) {
                continue;
            }
            $cells = array_values($row instanceof Collection ? $row->toArray() : (array) $row);
            if ($this->rowIsEmpty($cells)) {
                continue;
            }

            $dateRaw = $cells[0] ?? null;
            $monthRaw = $cells[1] ?? null;
            $riderId = trim((string) ($cells[2] ?? ''));
            $particulars = trim((string) ($cells[3] ?? ''));
            $amount = $this->toAmount($cells[4] ?? 0);

            $parsed[] = [
                'd' => $this->formatDisplayDate($dateRaw),
                'm' => $this->formatDisplayMonth($monthRaw),
                'id' => $riderId,
                'p' => $particulars,
                'a' => $amount,
            ];
        }

        return $this->annotateRows($parsed);
    }

    /**
     * Re-validate editable rows (after client edits).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, counts: array{ok:int,err:int,warn:int}}
     */
    public function validateRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized[] = [
                'd' => trim((string) ($row['d'] ?? '')),
                'm' => $this->normalizeMonthLabel(trim((string) ($row['m'] ?? ''))),
                'id' => trim((string) ($row['id'] ?? '')),
                'p' => trim((string) ($row['p'] ?? '')),
                'a' => $this->toAmount($row['a'] ?? 0),
            ];
        }

        return $this->annotateRows($normalized);
    }

    /**
     * Create vouchers from validated rows (skips rows with errors).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{created:int,skipped:int,errors:list<string>}
     */
    public function commit(int $paymentFrom, array $rows): array
    {
        $annotated = $this->validateRows($rows);
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($annotated['rows'] as $i => $row) {
            $line = $i + 1;
            if (! empty($row['errors'])) {
                $skipped++;
                $errors[] = 'Row '.$line.': '.implode('; ', array_values($row['errors']));
                continue;
            }

            $rider = Riders::where('rider_id', $row['id'])->first();
            if (! $rider || empty($rider->branch_id) || empty($rider->account_id)) {
                $skipped++;
                $errors[] = 'Row '.$line.': rider account/branch missing.';
                continue;
            }

            try {
                DB::transaction(function () use ($row, $rider, $paymentFrom, &$created) {
                    $date = $this->parseDisplayDate($row['d']);
                    $billingMonth = $this->parseDisplayMonth($row['m']);
                    $amount = (float) $row['a'];
                    $narration = (string) $row['p'];
                    $transCode = Account::trans_code();

                    $voucher = Vouchers::create([
                        'remarks' => 'Journal Voucher',
                        'amount' => $amount,
                        'payment_type' => 0,
                        'voucher_type' => 'JV',
                        'posting_date' => $date,
                        'trans_date' => $date,
                        'billing_month' => $billingMonth,
                        'payment_to' => $rider->account_id,
                        'payment_from' => $paymentFrom,
                        'Created_By' => auth()->id(),
                        'trans_code' => $transCode,
                        'branch_id' => $rider->branch_id,
                        'rider_id' => $rider->id,
                        'custom_field_values' => [],
                    ]);

                    $tx = new TransactionService();
                    $tx->recordTransaction([
                        'account_id' => $rider->account_id,
                        'reference_id' => $voucher->id,
                        'reference_type' => 'Voucher',
                        'trans_code' => $transCode,
                        'trans_date' => $date,
                        'narration' => $narration,
                        'debit' => $amount,
                        'billing_month' => $billingMonth,
                        'branch_id' => $rider->branch_id,
                    ]);
                    $tx->recordTransaction([
                        'account_id' => $paymentFrom,
                        'reference_id' => $voucher->id,
                        'reference_type' => 'Voucher',
                        'trans_code' => $transCode,
                        'trans_date' => $date,
                        'narration' => $narration,
                        'credit' => $amount,
                        'billing_month' => $billingMonth,
                        'branch_id' => $rider->branch_id,
                    ]);

                    $created++;
                });
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = 'Row '.$line.': '.$e->getMessage();
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, counts: array{ok:int,err:int,warn:int}}
     */
    private function annotateRows(array $rows): array
    {
        $riderIds = collect($rows)->pluck('id')->filter()->unique()->values()->all();
        $riders = Riders::query()
            ->whereIn('rider_id', $riderIds)
            ->get(['id', 'rider_id', 'name', 'branch_id', 'account_id', 'status'])
            ->keyBy(fn ($r) => strtoupper((string) $r->rider_id));

        $seen = [];
        $out = [];
        $ok = 0;
        $err = 0;
        $warn = 0;

        foreach ($rows as $i => $row) {
            $errors = [];
            $warnings = [];

            $date = (string) ($row['d'] ?? '');
            if (! preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                $errors['d'] = 'Date missing or wrong (dd-mm-yyyy)';
            } else {
                try {
                    $parsed = Carbon::createFromFormat('d-m-Y', $date)->startOfDay();
                    if ($parsed->gt(now()->endOfDay())) {
                        $errors['d'] = 'Date is in the future';
                    }
                } catch (\Throwable $e) {
                    $errors['d'] = 'Date missing or wrong (dd-mm-yyyy)';
                }
            }

            $month = $this->normalizeMonthLabel((string) ($row['m'] ?? ''));
            $row['m'] = $month;
            if (! preg_match('/^[A-Za-z]{3}-\d{4}$/', $month)) {
                $errors['m'] = 'Billing Month missing (e.g. Nov-2026)';
            }

            $riderKey = strtoupper((string) ($row['id'] ?? ''));
            $name = '';
            if ($riderKey === '') {
                $errors['id'] = 'Rider ID missing';
            } elseif (! isset($riders[$riderKey])) {
                $errors['id'] = 'Rider ID not found';
            } else {
                $rider = $riders[$riderKey];
                $name = (string) ($rider->name ?? '');
                if (empty($rider->branch_id)) {
                    $errors['id'] = 'Rider has no branch';
                } elseif (empty($rider->account_id)) {
                    $errors['id'] = 'Rider has no account';
                }

                $dupKey = $riderKey.'|'.$month;
                if (isset($seen[$dupKey])) {
                    $errors['id'] = 'Duplicate of row '.($seen[$dupKey] + 1);
                } else {
                    $seen[$dupKey] = $i;
                }

                $status = strtolower((string) ($rider->status ?? ''));
                if ($status !== '' && ! in_array($status, ['active', '1'], true)) {
                    $warnings[] = 'Rider status: '.$rider->status;
                }
            }

            $amount = $this->toAmount($row['a'] ?? 0);
            $row['a'] = $amount;
            if (! ($amount > 0)) {
                $errors['a'] = 'Amount missing or zero';
            }

            if (trim((string) ($row['p'] ?? '')) === '') {
                $warnings[] = 'Particulars is empty';
            }

            $row['name'] = $name;
            $row['errors'] = $errors;
            $row['warnings'] = $warnings;

            if ($errors !== []) {
                $err++;
            } else {
                $ok++;
                if ($warnings !== []) {
                    $warn++;
                }
            }

            $out[] = $row;
        }

        return [
            'rows' => $out,
            'counts' => ['ok' => $ok, 'err' => $err, 'warn' => $warn],
        ];
    }

    private function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private function toAmount(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }
        $clean = preg_replace('/[^\d.\-]/', '', (string) $value);

        return round((float) $clean, 2);
    }

    private function formatDisplayDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            $ymd = ExcelDate::format($value);
            if (! $ymd) {
                return trim((string) $value);
            }

            return Carbon::parse($ymd)->format('d-m-Y');
        } catch (\Throwable $e) {
            $raw = trim((string) $value);
            if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $raw, $m)) {
                return sprintf('%02d-%02d-%04d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }

            return $raw;
        }
    }

    private function formatDisplayMonth(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            $ymd = ExcelDate::format($value);
            if ($ymd) {
                return Carbon::parse($ymd)->format('M-Y');
            }
        } catch (\Throwable $e) {
        }

        return $this->normalizeMonthLabel((string) $value);
    }

    private function normalizeMonthLabel(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^([A-Za-z]{3})-(\d{2})$/', $value, $m)) {
            return ucfirst(strtolower($m[1])).'-20'.$m[2];
        }
        if (preg_match('/^([A-Za-z]{3})-(\d{4})$/', $value, $m)) {
            return ucfirst(strtolower($m[1])).'-'.$m[2];
        }
        try {
            if (preg_match('/^\d{4}-\d{2}/', $value)) {
                return Carbon::parse($value)->format('M-Y');
            }
        } catch (\Throwable $e) {
        }

        return $value;
    }

    private function parseDisplayDate(string $value): string
    {
        return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
    }

    private function parseDisplayMonth(string $value): string
    {
        $value = $this->normalizeMonthLabel($value);

        return Carbon::createFromFormat('M-Y', $value)->format('Y-m-01');
    }
}
