<?php

namespace App\Imports;

use App\Helpers\Account;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Payment;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\ExcelSlashDateFormat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportPaidRiderInvoice implements ToCollection
{
    protected array $columnMap;

    /** @var string ExcelSlashDateFormat::ORDER_DMY|ORDER_MDY */
    protected string $slashDateOrder = ExcelSlashDateFormat::ORDER_DMY;

    public int $importedCount = 0;

    /** @var array<int, string> */
    public array $skippedLog = [];

    public function __construct(array $columnMap = [])
    {
        $this->columnMap = $columnMap ?: $this->defaultColumnMap();
    }

    /**
     * Default 1-based columns used when mapping is omitted.
     */
    private function defaultColumnMap(): array
    {
        return [
            'rider_id' => 2,
            'billing_month' => 11,
            'account_code' => 33,
            'amount' => 4,
            'payment_date' => 1,
            'description' => 5,
        ];
    }

    private function cell($row, string $key)
    {
        $col = $this->columnMap[$key] ?? null;
        if ($col === null || $col === '') {
            return null;
        }

        $index = ((int) $col) - 1;

        return $row[$index] ?? null;
    }

    private function isMapped(string $key): bool
    {
        $col = $this->columnMap[$key] ?? null;

        return $col !== null && $col !== '';
    }

    private function isBlank($value): bool
    {
        return $value === null || (is_string($value) && trim((string) $value) === '');
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $this->slashDateOrder = $this->detectSheetSlashDateOrder($rows);

        foreach ($rows->skip(1) as $rowIndex => $row) {
            $excelRow = is_numeric($rowIndex) ? ((int) $rowIndex + 1) : ($this->importedCount + count($this->skippedLog) + 2);

            $riderIdRaw = $this->cell($row, 'rider_id');
            $billingRaw = $this->cell($row, 'billing_month');
            $accountCodeRaw = $this->cell($row, 'account_code');
            $amountRaw = $this->cell($row, 'amount');
            $paymentDateRaw = $this->cell($row, 'payment_date');
            $descriptionRaw = $this->cell($row, 'description');

            if (
                $this->isBlank($riderIdRaw)
                && $this->isBlank($billingRaw)
                && $this->isBlank($accountCodeRaw)
                && $this->isBlank($amountRaw)
                && $this->isBlank($paymentDateRaw)
                && $this->isBlank($descriptionRaw)
            ) {
                continue;
            }

            try {
                DB::beginTransaction();

                if ($this->isBlank($riderIdRaw)) {
                    throw new \RuntimeException('Rider ID is missing.');
                }
                if ($this->isBlank($billingRaw)) {
                    throw new \RuntimeException('Billing month is missing.');
                }
                if ($this->isBlank($accountCodeRaw)) {
                    throw new \RuntimeException('Paying account code is missing.');
                }
                if ($this->isBlank($amountRaw)) {
                    throw new \RuntimeException('Amount is missing.');
                }
                if ($this->isBlank($paymentDateRaw)) {
                    throw new \RuntimeException('Payment date is missing.');
                }
                if ($this->isBlank($descriptionRaw)) {
                    throw new \RuntimeException('Description is missing.');
                }

                $riderId = trim((string) $riderIdRaw);
                $rider = Riders::where('rider_id', $riderId)->first();
                if (! $rider) {
                    throw new \RuntimeException('Rider ID '.$riderId.' does not exist.');
                }
                if (empty($rider->account_id)) {
                    throw new \RuntimeException('Rider '.$riderId.' has no account.');
                }

                $billingMonth = $this->parseBillingMonth($billingRaw);
                if (! $billingMonth) {
                    throw new \RuntimeException('Invalid billing month.');
                }

                $invoice = RiderInvoices::query()
                    ->where('rider_id', $rider->id)
                    ->whereYear('billing_month', (int) $billingMonth->format('Y'))
                    ->whereMonth('billing_month', (int) $billingMonth->format('m'))
                    ->payable()
                    ->orderByDesc('id')
                    ->first();

                if (! $invoice) {
                    throw new \RuntimeException('No unpaid invoice found for rider '.$riderId.' in '.$billingMonth->format('M Y').'.');
                }

                $payingAccount = $this->findPayingAccountByCode(trim((string) $accountCodeRaw));
                if (! $payingAccount) {
                    throw new \RuntimeException('Paying account code '.trim((string) $accountCodeRaw).' was not found.');
                }

                $bank = Banks::where('account_id', $payingAccount->id)->first();
                if (! $bank) {
                    throw new \RuntimeException('No bank is linked to account code '.$payingAccount->account_code.'.');
                }

                $paymentAmount = $this->resolveAmount($row);
                if ($paymentAmount < 0.01) {
                    throw new \RuntimeException('Payment amount must be greater than 0.');
                }

                $paymentDate = $this->resolvePaymentDate($row);
                $description = $this->resolveDescription($row);

                $this->createInvoicePayment(
                    $invoice,
                    $rider,
                    $bank,
                    $payingAccount,
                    $paymentAmount,
                    $paymentDate,
                    $description
                );

                $invoice->update([
                    'status' => 1,
                    'updated_by' => Auth::id(),
                ]);

                DB::commit();
                $this->importedCount++;
            } catch (\RuntimeException $e) {
                DB::rollBack();
                $this->skippedLog[] = 'Row '.$excelRow.': '.$e->getMessage();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->skippedLog[] = 'Row '.$excelRow.': '.$e->getMessage();
            }
        }
    }

    /**
     * Create the same Payment + PV voucher used when recording an individual rider invoice payment.
     */
    private function createInvoicePayment(
        RiderInvoices $invoice,
        Riders $rider,
        Banks $bank,
        Accounts $payingAccount,
        float $paymentAmount,
        Carbon $paymentDate,
        string $description
    ): void {
        $payeeAccount = Accounts::find($rider->account_id);
        $branchId = $payeeAccount->branch_id ?? $payingAccount->branch_id ?? $invoice->branch_id;
        $billingMonth = Carbon::parse($invoice->billing_month)->format('Y-m-01');
        $invoiceDate = $invoice->inv_date
            ? Carbon::parse($invoice->inv_date)->format('Y-m-d')
            : $paymentDate->format('Y-m-d');

        $payment = Payment::create([
            'branch_id' => $branchId,
            'reference' => $invoice->invoice_number,
            'bank_charges' => 0,
            'bank_id' => $bank->id,
            'amount_type' => 'Online',
            'payee_account_id' => $rider->account_id,
            'amount' => $paymentAmount,
            'date_of_invoice' => $invoiceDate,
            'date_of_payment' => $paymentDate->format('Y-m-d'),
            'billing_month' => $billingMonth,
            'description' => $description,
            'created_by' => Auth::id(),
        ]);

        $transCode = Account::trans_code();
        $date = $paymentDate->format('Y-m-d');

        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $date,
            'reference_id' => $payment->id,
            'reference_type' => 'PV',
            'account_id' => $payingAccount->id,
            'credit' => $paymentAmount,
            'debit' => 0,
            'billing_month' => $billingMonth,
            'narration' => $description,
            'branch_id' => $branchId,
        ]);

        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $date,
            'reference_id' => $payment->id,
            'reference_type' => 'PV',
            'account_id' => $rider->account_id,
            'credit' => 0,
            'debit' => $paymentAmount,
            'billing_month' => $billingMonth,
            'narration' => $description,
            'branch_id' => $branchId,
        ]);

        $voucher = Vouchers::create([
            'trans_date' => $date,
            'trans_code' => $transCode,
            'reference_number' => $payment->reference,
            'billing_month' => $billingMonth,
            'payment_from' => $payingAccount->id,
            'amount' => $paymentAmount,
            'voucher_type' => 'PV',
            'remarks' => 'Payment Voucher',
            'ref_id' => $payment->id,
            'Created_By' => Auth::id(),
            'status' => 1,
            'branch_id' => $branchId,
        ]);

        $payment->update([
            'voucher_id' => $voucher->id,
        ]);
    }

    private function findPayingAccountByCode(string $accountCode): ?Accounts
    {
        $accountCode = trim($accountCode);
        if ($accountCode === '') {
            return null;
        }

        return Accounts::where('account_code', $accountCode)->first()
            ?: Accounts::whereRaw('TRIM(account_code) = ?', [$accountCode])->first();
    }

    private function resolveAmount($row): float
    {
        $raw = $this->cell($row, 'amount');
        if (is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $raw);

        return ($cleaned !== '' && is_numeric($cleaned)) ? round((float) $cleaned, 2) : 0.0;
    }

    private function resolvePaymentDate($row): Carbon
    {
        $parsed = $this->parseExcelDate($this->cell($row, 'payment_date'));
        if (! $parsed) {
            throw new \RuntimeException('Invalid payment date.');
        }

        return $parsed;
    }

    private function resolveDescription($row): string
    {
        return trim((string) $this->cell($row, 'description'));
    }

    private function parseBillingMonth($value): ?Carbon
    {
        $parsed = $this->parseExcelDate($value);
        if ($parsed) {
            return $parsed->copy()->startOfMonth();
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        foreach (['Y-m', 'Y/m', 'm/Y', 'M Y', 'F Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                return $date->startOfMonth();
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function detectSheetSlashDateOrder(Collection $rows): string
    {
        $samples = [];
        foreach ($rows->take(80) as $row) {
            foreach (['billing_month', 'payment_date'] as $key) {
                if ($this->isMapped($key)) {
                    $samples[] = $this->cell($row, $key);
                }
            }
        }

        return ExcelSlashDateFormat::detectOrder($samples);
    }

    private function parseExcelDate($value): ?Carbon
    {
        if ($this->isBlank($value)) {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->copy();
            }
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            $value = trim((string) $value);
            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $value)) {
                return ExcelSlashDateFormat::parse($value, $this->slashDateOrder);
            }

            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
