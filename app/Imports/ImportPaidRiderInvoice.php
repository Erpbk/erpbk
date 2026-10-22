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
use App\Support\CompanyContext;
use App\Support\ExcelDate;
use App\Support\ExcelSlashDateFormat;
use App\Support\ResourceLinks;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

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
            'invoice_number' => 1,
            'rider_id' => 2,
            'billing_month' => 11,
            'account_code' => 33,
            'amount' => 4,
            'payment_date' => 3,
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

            $invoiceNumberRaw = $this->cell($row, 'invoice_number');
            $riderIdRaw = $this->isMapped('rider_id') ? $this->cell($row, 'rider_id') : null;
            $billingRaw = $this->isMapped('billing_month') ? $this->cell($row, 'billing_month') : null;
            $accountCodeRaw = $this->cell($row, 'account_code');
            $amountRaw = $this->cell($row, 'amount');
            $paymentDateRaw = $this->cell($row, 'payment_date');
            $descriptionRaw = $this->cell($row, 'description');

            if (
                $this->isBlank($invoiceNumberRaw)
                && $this->isBlank($riderIdRaw)
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

                if ($this->isBlank($invoiceNumberRaw)) {
                    throw new \RuntimeException('Invoice number is missing.');
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

                $invoice = $this->resolveInvoiceByNumber(trim((string) $invoiceNumberRaw));
                if (! $invoice) {
                    throw new \RuntimeException('Invoice '.trim((string) $invoiceNumberRaw).' was not found.');
                }

                $invoice->loadMissing(['rider.account', 'items']);
                $rider = $invoice->rider;
                if (! $rider) {
                    throw new \RuntimeException('Invoice '.$invoice->invoice_number.' has no rider.');
                }
                if (empty($rider->account_id)) {
                    throw new \RuntimeException('Rider '.$rider->rider_id.' has no account.');
                }

                if ($this->isMapped('rider_id') && ! $this->isBlank($riderIdRaw)) {
                    $mappedRiderId = trim((string) $riderIdRaw);
                    if (strcasecmp((string) $rider->rider_id, $mappedRiderId) !== 0) {
                        throw new \RuntimeException(
                            'Rider ID '.$mappedRiderId.' does not match invoice '.$invoice->invoice_number
                            .' (expected '.$rider->rider_id.').'
                        );
                    }
                }

                if ($this->isMapped('billing_month') && ! $this->isBlank($billingRaw)) {
                    $billingMonth = $this->parseBillingMonth($billingRaw);
                    if (! $billingMonth) {
                        throw new \RuntimeException('Invalid billing month.');
                    }
                    $invoiceMonth = Carbon::parse($invoice->billing_month);
                    if (
                        (int) $billingMonth->format('Y') !== (int) $invoiceMonth->format('Y')
                        || (int) $billingMonth->format('m') !== (int) $invoiceMonth->format('m')
                    ) {
                        throw new \RuntimeException(
                            'Billing month '.$billingMonth->format('M Y')
                            .' does not match invoice '.$invoice->invoice_number
                            .' ('.$invoiceMonth->format('M Y').').'
                        );
                    }
                }

                // Already paid / settled invoices must not create a payment.
                if ((int) $invoice->status === 1) {
                    throw new \RuntimeException(
                        'Invoice '.$invoice->invoice_number.' is already fully paid. Payment was not stored.'
                    );
                }

                $isPayable = $invoice->status === null || in_array((int) $invoice->status, [0, 3], true);
                if (! $isPayable) {
                    throw new \RuntimeException(
                        'Invoice '.$invoice->invoice_number.' cannot receive a payment. Payment was not stored.'
                    );
                }

                $invoice->clearOutstandingSummary();
                $balanceBefore = round((float) ($invoice->balance ?? 0), 2);
                if ($balanceBefore <= 0.01) {
                    throw new \RuntimeException(
                        'Invoice '.$invoice->invoice_number.' has no outstanding balance. Payment was not stored.'
                    );
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
                if ($paymentAmount > $balanceBefore + 0.01) {
                    throw new \RuntimeException(
                        'Payment amount exceeds outstanding balance ('
                        .number_format($balanceBefore, 2).') for invoice '.$invoice->invoice_number.'.'
                    );
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
                    $description,
                    $balanceBefore
                );

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
     * Resolve a rider invoice from RINV-00012, RINV-12, or a bare numeric id.
     */
    private function resolveInvoiceByNumber(string $raw): ?RiderInvoices
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $id = null;
        if (preg_match('/^RINV-?0*(\d+)$/i', $raw, $m)) {
            $id = (int) $m[1];
        } elseif (preg_match('/^0*(\d+)$/', $raw, $m)) {
            $id = (int) $m[1];
        }

        if ($id === null || $id <= 0) {
            return null;
        }

        return RiderInvoices::query()->lockForUpdate()->find($id);
    }

    /**
     * Create the same Payment + PV voucher used when recording a manual rider salary payment.
     */
    private function createInvoicePayment(
        RiderInvoices $invoice,
        Riders $rider,
        Banks $bank,
        Accounts $payingAccount,
        float $paymentAmount,
        Carbon $paymentDate,
        string $description,
        float $balanceBefore
    ): void {
        $payeeAccount = Accounts::find($rider->account_id);
        $companyId = $invoice->company_id ?? $rider->company_id ?? CompanyContext::id();
        if (empty($companyId)) {
            throw new \RuntimeException('Cannot create payment without company_id.');
        }
        $branchId = $payeeAccount->branch_id ?? $payingAccount->branch_id ?? $invoice->branch_id;
        $billingMonth = Carbon::parse($invoice->billing_month)->format('Y-m-01');
        $invoiceDate = $invoice->inv_date
            ? Carbon::parse($invoice->inv_date)->format('Y-m-d')
            : $paymentDate->format('Y-m-d');

        $invoiceTag = 'Rider Invoice #'.$invoice->id;
        $description = trim($description);
        if ($description === '') {
            $description = 'Payment against '.$invoiceTag;
        } elseif (! str_contains($description, 'Rider Invoice #')) {
            $description = $description.' | '.$invoiceTag;
        }

        $payment = Payment::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'reference' => $invoice->invoice_number,
            'resource_links' => ResourceLinks::build(
                'rider',
                [(int) $invoice->id],
                [(int) $invoice->id => $paymentAmount],
                collect([$invoice]),
                null,
                ResourceLinks::SOURCE_IMPORT
            ),
            'bank_charges' => 0,
            'bank_id' => $bank->id,
            'amount_type' => 'Online',
            'payment_kind' => 'salary_payment',
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
            'company_id' => $companyId,
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
            'company_id' => $companyId,
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
            'company_id' => $companyId,
            'trans_date' => $date,
            'trans_code' => $transCode,
            'reference_number' => $payment->reference,
            'billing_month' => $billingMonth,
            'payment_from' => $payingAccount->id,
            'amount' => $paymentAmount,
            'voucher_type' => 'PV',
            'remarks' => 'Salary Payment',
            'ref_id' => $payment->id,
            'rider_id' => $rider->id,
            'Created_By' => Auth::id(),
            'status' => 1,
            'branch_id' => $branchId,
        ]);

        $payment->update([
            'voucher_id' => $voucher->id,
        ]);

        $partialAmount = $invoice->partial_paid_amount ?? [];
        if (! is_array($partialAmount)) {
            $partialAmount = [];
        }
        $partialAmount[(string) $payment->id] = $paymentAmount;

        // Full settlement of outstanding → invoice paid (status 1); otherwise partial (3).
        $fullyPaid = round($paymentAmount, 2) >= round($balanceBefore - 0.01, 2);

        $invoice->update([
            'status' => $fullyPaid ? 1 : 3,
            'partial_paid_amount' => $partialAmount,
            'updated_by' => Auth::id(),
        ]);

        $invoice->clearOutstandingSummary();
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
        $parsed = ExcelDate::parse($this->cell($row, 'payment_date'), $this->slashDateOrder);
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
        return ExcelDate::parseBillingMonth($value, $this->slashDateOrder);
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
}
